#!/usr/bin/env python3

import argparse
import json
import os
import stat
import sys
import time
from pathlib import Path

from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait


def required_env(name: str) -> str:
    value = os.environ.get(name, "").strip()
    if not value:
        raise RuntimeError(f"Variável obrigatória não configurada: {name}")
    return value


def clear_stale_profile_lock(profile_dir: Path) -> None:
    lock = profile_dir / "SingletonLock"
    if not lock.is_symlink():
        return

    try:
        pid = int(os.readlink(lock).rsplit("-", 1)[1])
        os.kill(pid, 0)
        process_state = Path(f"/proc/{pid}/stat").read_text(encoding="utf-8").split()[2]
        if process_state != "Z":
            return
    except (ValueError, IndexError, OSError):
        pass

    for name in ("SingletonLock", "SingletonCookie", "SingletonSocket"):
        path = profile_dir / name
        if path.is_symlink():
            path.unlink()


def build_driver(profile_dir: Path) -> webdriver.Chrome:
    clear_stale_profile_lock(profile_dir)
    options = webdriver.ChromeOptions()
    options.binary_location = os.environ.get(
        "LOCALIZADOR_EDITAIS_BROWSER_BINARY", "/usr/bin/google-chrome"
    )
    options.add_argument(f"--user-data-dir={profile_dir}")
    options.add_argument("--no-first-run")
    options.add_argument("--no-default-browser-check")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--no-sandbox")
    options.add_argument("--window-size=1440,1000")
    options.add_argument("--remote-debugging-pipe")

    if os.environ.get("LOCALIZADOR_EDITAIS_BROWSER_HEADLESS", "true").lower() != "false":
        options.add_argument("--headless=new")

    driver_log = os.environ.get("LOCALIZADOR_EDITAIS_DRIVER_LOG", os.devnull)
    service = Service(log_output=driver_log, service_args=["--verbose"] if driver_log != os.devnull else None)

    return webdriver.Chrome(service=service, options=options)


def wordpress_session_exists(driver: webdriver.Chrome) -> bool:
    return any(cookie["name"].startswith("wordpress_logged_in_") for cookie in driver.get_cookies())


def login(driver: webdriver.Chrome, base_url: str, username: str, password: str) -> None:
    driver.get(f"{base_url}/")
    wait = WebDriverWait(driver, 45)

    if wordpress_session_exists(driver):
        return

    username_input = wait.until(EC.element_to_be_clickable((By.NAME, "user_login")))
    password_input = wait.until(EC.element_to_be_clickable((By.NAME, "user_pass")))
    username_input.clear()
    username_input.send_keys(username)
    password_input.clear()
    password_input.send_keys(password)

    form = password_input.find_element(By.XPATH, "ancestor::form")
    submit = form.find_element(By.CSS_SELECTOR, "button[type='submit'], input[type='submit']")
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", submit)
    submit.click()

    try:
        WebDriverWait(driver, 60).until(lambda current: wordpress_session_exists(current))
    except TimeoutException as error:
        messages = form.find_elements(By.CSS_SELECTOR, ".arm_error_msg, .arm-df__fc--validation, .error")
        detail = " ".join(message.text.strip() for message in messages if message.text.strip())
        raise RuntimeError(f"O ARMember não concluiu o login. {detail}".strip()) from error


def fetch_rest_nonce(driver: webdriver.Chrome, base_url: str) -> str:
    driver.get(f"{base_url}/buscador/")
    nonce = driver.execute_async_script(
        """
        const done = arguments[arguments.length - 1];
        fetch(arguments[0] + '/wp-admin/admin-ajax.php?action=rest-nonce', {
            credentials: 'include',
            cache: 'no-store'
        }).then(async response => done({status: response.status, body: await response.text()}))
          .catch(error => done({status: 0, body: String(error)}));
        """,
        base_url,
    )

    value = nonce["body"].strip()
    if nonce["status"] != 200 or not value.isalnum():
        raise RuntimeError(f"Não foi possível obter o nonce REST (HTTP {nonce['status']}).")
    return value


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", required=True)
    parser.add_argument("--profile-dir", required=True)
    args = parser.parse_args()

    base_url = required_env("LOCALIZADOR_EDITAIS_BASE_URL").rstrip("/")
    username = required_env("LOCALIZADOR_EDITAIS_USERNAME")
    password = required_env("LOCALIZADOR_EDITAIS_PASSWORD")
    output = Path(args.output)
    profile_dir = Path(args.profile_dir)
    profile_dir.mkdir(parents=True, exist_ok=True)
    output.parent.mkdir(parents=True, exist_ok=True)

    driver = build_driver(profile_dir)
    try:
        login(driver, base_url, username, password)
        nonce = fetch_rest_nonce(driver, base_url)
        cookies = "; ".join(
            f"{cookie['name']}={cookie['value']}" for cookie in driver.get_cookies()
        )
        if not cookies:
            raise RuntimeError("O navegador não retornou cookies após o login.")

        output.write_text(json.dumps({"cookie": cookies, "nonce": nonce}), encoding="utf-8")
        output.chmod(stat.S_IRUSR | stat.S_IWUSR)
        return 0
    finally:
        driver.quit()


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as error:
        if "--output" in sys.argv:
            try:
                error_output = Path(sys.argv[sys.argv.index("--output") + 1])
                error_output.parent.mkdir(parents=True, exist_ok=True)
                error_output.write_text(json.dumps({"error": str(error)}), encoding="utf-8")
                error_output.chmod(stat.S_IRUSR | stat.S_IWUSR)
            except Exception:
                pass
        print(str(error), file=sys.stderr)
        sys.exit(1)
