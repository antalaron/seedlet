/**
 * Handles the global speed limits modal.
 */
class SettingsModal {
  constructor ({ api, element, onSessionChanged }) {
    this.api = api;
    this.element = element;
    this.onSessionChanged = onSessionChanged;
    this.form = element.querySelector('#settings-form');
    this.errorBox = element.querySelector('#settings-error');
    this.downloadEnabled = element.querySelector('#settings-download-enabled');
    this.downloadLimit = element.querySelector('#settings-download-limit');
    this.uploadEnabled = element.querySelector('#settings-upload-enabled');
    this.uploadLimit = element.querySelector('#settings-upload-limit');
    this.turtleState = element.querySelector('#settings-turtle-state');
  }

  init () {
    this.element.addEventListener('show.bs.modal', () => this.load());
    this.form.addEventListener('submit', (event) => this.handleSubmit(event));
  }

  showError (message) {
    if (message) {
      this.errorBox.textContent = message;
      this.errorBox.classList.remove('d-none');
    } else {
      this.errorBox.classList.add('d-none');
    }
  }

  async load () {
    this.showError(null);

    try {
      const { session } = await this.api.getSession();
      this.applySession(session);
    } catch (error) {
      this.showError(error.message);
    }
  }

  applySession (session) {
    this.downloadEnabled.checked = session.speedLimitDownEnabled;
    this.downloadLimit.value = session.speedLimitDown;
    this.uploadEnabled.checked = session.speedLimitUpEnabled;
    this.uploadLimit.value = session.speedLimitUp;
    this.turtleState.textContent = session.altSpeedEnabled ? 'enabled' : 'disabled';
  }

  async handleSubmit (event) {
    event.preventDefault();
    this.showError(null);

    try {
      const { session } = await this.api.updateSession({
        speedLimitDownEnabled: this.downloadEnabled.checked,
        speedLimitDown: Number(this.downloadLimit.value),
        speedLimitUpEnabled: this.uploadEnabled.checked,
        speedLimitUp: Number(this.uploadLimit.value)
      });
      this.applySession(session);
      this.onSessionChanged(session);
    } catch (error) {
      this.showError(error.message);
    }
  }
}

export default SettingsModal;
