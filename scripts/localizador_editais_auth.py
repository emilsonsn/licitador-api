#!/usr/bin/env python3

import argparse
import json
import os
import stat
import sys
import time
from pathlib import Path
from urllib.parse import parse_qsl

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


def prepare_browser_environment(profile_dir: Path) -> None:
    browser_home = profile_dir.parent / "localizador-editais-browser-home"
    config_home = browser_home / ".config"
    cache_home = browser_home / ".cache"
    data_home = browser_home / ".local" / "share"

    for directory in (
        browser_home,
        config_home,
        cache_home,
        data_home,
        data_home / "applications",
        config_home / "google-chrome" / "Crash Reports",
    ):
        directory.mkdir(parents=True, exist_ok=True)

    os.environ["HOME"] = str(browser_home)
    os.environ["XDG_CONFIG_HOME"] = str(config_home)
    os.environ["XDG_CACHE_HOME"] = str(cache_home)
    os.environ["XDG_DATA_HOME"] = str(data_home)


def build_driver(profile_dir: Path) -> webdriver.Chrome:
    prepare_browser_environment(profile_dir)
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
    options.add_argument("--disable-breakpad")
    options.add_argument("--disable-crash-reporter")

    if os.environ.get("LOCALIZADOR_EDITAIS_BROWSER_HEADLESS", "true").lower() != "false":
        options.add_argument("--headless=new")

    driver_log = os.environ.get("LOCALIZADOR_EDITAIS_DRIVER_LOG", os.devnull)
    service = Service(log_output=driver_log, service_args=["--verbose"] if driver_log != os.devnull else None)

    return webdriver.Chrome(service=service, options=options)


def wordpress_session_exists(driver: webdriver.Chrome) -> bool:
    return any(cookie["name"].startswith("wordpress_logged_in_") for cookie in driver.get_cookies())


def initialize_armember_session(driver: webdriver.Chrome) -> None:
    result = driver.execute_async_script(
        """
        const done = arguments[arguments.length - 1];
        const password = document.querySelector('[name="user_pass"]');
        const form = password?.closest('form');
        const forms = [...document.querySelectorAll('form[data-random-id]')];
        const randomIds = forms.map(item => item.dataset.randomId).filter(Boolean);
        const nonce = form?.querySelector('[name="arm_wp_nonce"]')?.value;
        if (!form || !randomIds.length || !nonce || !window.jQuery || !window.__ARMAJAXURL) {
            done({ok: false, error: 'form-not-ready'});
            return;
        }

        window.jQuery.ajax({
            type: 'POST',
            url: window.__ARMAJAXURL,
            dataType: 'json',
            data: {
                action: 'arm_reinit_session_multiple_form',
                form_key_arr: randomIds.join(','),
                _wpnonce: nonce
            },
            success: function(payload) {
                for (const currentForm of forms) {
                    const fieldName = payload?.[currentForm.dataset.randomId];
                    if (!fieldName) {
                        done({ok: false, error: 'missing-session-field'});
                        return;
                    }
                    const inputs = [...currentForm.querySelectorAll('input')].filter(input =>
                        !['ct_bot_detector_event_token', 'apbct_visible_fields',
                          'ct_no_cookie_hidden_field', 'armrplogin', 'armrpkey'].includes(input.name)
                    );
                    inputs[inputs.length - 1].name = fieldName;
                }
                if (payload.nonce) {
                    form.querySelector('[name="arm_wp_nonce"]').value = payload.nonce;
                }
                done({ok: true});
            },
            error: function(xhr) {
                done({ok: false, error: 'http-' + xhr.status});
            }
        });
        """
    )
    if not result.get("ok"):
        raise RuntimeError(
            f"O ARMember não inicializou a sessão antispam ({result.get('error', 'erro desconhecido')})."
        )


def try_native_wordpress_login(
    driver: webdriver.Chrome, base_url: str, username: str, password: str
) -> bool:
    result = driver.execute_async_script(
        """
        const done = arguments[arguments.length - 1];
        const body = new URLSearchParams({
            log: arguments[1],
            pwd: arguments[2],
            'wp-submit': 'Log In',
            redirect_to: arguments[0] + '/buscador/',
            testcookie: '1'
        });
        fetch(arguments[0] + '/wp-login.php', {
            method: 'POST',
            credentials: 'include',
            cache: 'no-store',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
        }).then(response => done({ok: response.ok, url: response.url}))
          .catch(error => done({ok: false, error: String(error)}));
        """,
        base_url,
        username,
        password,
    )
    return bool(result.get("ok") and wordpress_session_exists(driver))


def login(driver: webdriver.Chrome, base_url: str, username: str, password: str) -> None:
    driver.get(f"{base_url}/")
    wait = WebDriverWait(driver, 45)

    if wordpress_session_exists(driver):
        return

    if try_native_wordpress_login(driver, base_url, username, password):
        return

    driver.execute_script(
        """
        window.__armLoginResponses = [];
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function(method, url) {
            this.__trackedUrl = String(url || '');
            return originalOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function() {
            this.__requestBody = typeof arguments[0] === 'string' ? arguments[0] : '';
            this.addEventListener('load', function() {
                if (this.__trackedUrl.includes('admin-ajax.php')) {
                    window.__armLoginResponses.push({
                        status: this.status,
                        requestBody: this.__requestBody,
                        body: String(this.responseText || '').slice(0, 4000)
                    });
                }
            });
            return originalSend.apply(this, arguments);
        };
        const originalFetch = window.fetch;
        window.fetch = async function() {
            const response = await originalFetch.apply(this, arguments);
            const url = String(arguments[0] || '');
            if (url.includes('admin-ajax.php')) {
                const copy = response.clone();
                window.__armLoginResponses.push({
                    status: response.status,
                    body: String(await copy.text()).slice(0, 4000)
                });
            }
            return response;
        };
        """
    )

    wait.until(
        lambda current: current.execute_script(
            """
            const password = document.querySelector('[name="user_pass"]');
            const form = password?.closest('form');
            const start = form?.querySelector('.stime');
            return Boolean(start?.name && start.value);
            """
        )
    )
    initialize_armember_session(driver)
    driver.execute_script("window.__armLoginResponses = [];")

    username_input = wait.until(EC.element_to_be_clickable((By.NAME, "user_login")))
    username_input.clear()
    username_input.send_keys(username)

    password_input = wait.until(EC.element_to_be_clickable((By.NAME, "user_pass")))
    password_input.clear()
    password_input.send_keys(password)
    time.sleep(float(os.environ.get("LOCALIZADOR_EDITAIS_LOGIN_DELAY", "5")))

    antispam_ready = driver.execute_script(
        """
        const password = document.querySelector('[name="user_pass"]');
        const form = password?.closest('form');
        const keypress = form?.querySelector('.kpress');
        const keypressName = form?.querySelector('.arm_nonce_keyboard_press')?.value;
        const captcha = form?.querySelector('input[name^="arm_captcha_"]');
        const googleCaptcha = form?.querySelector('[name="g-recaptcha-response"]');
        if (!keypress || !keypressName || !captcha?.value || !googleCaptcha) return false;
        keypress.name = keypressName;
        keypress.value = Math.max(1, arguments[0]);
        googleCaptcha.value = captcha.value;
        return true;
        """,
        len(username) + len(password),
    )
    if not antispam_ready:
        raise RuntimeError("O mecanismo antispam do ARMember não foi inicializado.")

    wait.until(
        lambda current: current.execute_script(
            "return typeof window.arm_form_ajax_action === 'function';"
        )
    )
    submitted = driver.execute_script(
        """
        const password = document.querySelector('[name="user_pass"]');
        const form = password?.closest('form');
        if (!form) return false;
        window.arm_form_ajax_action(window.jQuery(form));
        return true;
        """
    )

    if not submitted:
        raise RuntimeError("Não foi possível localizar o formulário de login do ARMember.")

    try:
        WebDriverWait(driver, 60).until(
            lambda current: wordpress_session_exists(current)
            or bool(current.execute_script("return window.__armLoginResponses?.length;"))
        )
        if not wordpress_session_exists(driver):
            raise TimeoutException()
    except TimeoutException as error:
        messages = driver.find_elements(By.CSS_SELECTOR, ".arm_error_msg, .arm-df__fc--validation, .error")
        detail = " ".join(message.text.strip() for message in messages if message.text.strip())
        responses = driver.execute_script("return window.__armLoginResponses || [];")
        for response in reversed(responses):
            try:
                payload = json.loads(response.get("body", ""))
                response_message = payload.get("message")
                if response_message:
                    request_fields = []
                    sensitive = {"user_login", "user_pass"}
                    for name, value in parse_qsl(
                        response.get("requestBody", ""), keep_blank_values=True
                    ):
                        if name in sensitive or name.startswith("arm_captcha_"):
                            continue
                        request_fields.append(f"{name}({len(value)})")
                    request_detail = ", ".join(request_fields)
                    detail = f"{detail} {response_message} [{request_detail}]".strip()
                    break
            except (TypeError, json.JSONDecodeError):
                continue
        if not detail:
            diagnostic = driver.execute_script(
                """
                const password = document.querySelector('[name="user_pass"]');
                const form = password?.closest('form');
                if (!form) return {url: location.href, form: false};
                const hidden = [...form.querySelectorAll('input[type="hidden"]')]
                    .filter(input => /captcha|spam|nonce|kpress|stime|start/i.test(
                        [input.name, input.id, input.className].join(' ')
                    ))
                    .map(input => ({
                        name: input.name || '',
                        class: input.className || '',
                        length: String(input.value || '').length
                    }));
                return {
                    url: location.href,
                    form: true,
                    valid: form.checkValidity(),
                    hidden: hidden,
                    text: String(form.innerText || '').replace(/\\s+/g, ' ').trim().slice(0, 1000)
                };
                """
            )
            detail = json.dumps(diagnostic, ensure_ascii=False)
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
