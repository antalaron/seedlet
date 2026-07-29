import { Modal } from 'bootstrap';

/**
 * Handles the "add torrent" modal (magnet/URL and file upload tabs).
 */
class AddTorrentModal {
  constructor ({ api, element, onAdded }) {
    this.api = api;
    this.element = element;
    this.onAdded = onAdded;
    this.modal = Modal.getOrCreateInstance(element);
    this.errorBox = element.querySelector('#add-torrent-error');
    this.uriForm = element.querySelector('#add-torrent-uri-form');
    this.fileForm = element.querySelector('#add-torrent-file-form');
  }

  init () {
    this.uriForm.addEventListener('submit', (event) => this.handleUriSubmit(event));
    this.fileForm.addEventListener('submit', (event) => this.handleFileSubmit(event));
    this.element.addEventListener('hidden.bs.modal', () => this.reset());
  }

  reset () {
    this.uriForm.reset();
    this.fileForm.reset();
    this.showError(null);
  }

  showError (message) {
    if (message) {
      this.errorBox.textContent = message;
      this.errorBox.classList.remove('d-none');
    } else {
      this.errorBox.classList.add('d-none');
    }
  }

  async handleUriSubmit (event) {
    event.preventDefault();

    const input = this.uriForm.querySelector('#add-torrent-uri-input');
    const startPaused = this.isStartPaused(this.uriForm);

    try {
      await this.api.addTorrentFromUri(input.value, startPaused);
      this.modal.hide();
      this.onAdded();
    } catch (error) {
      this.showError(error.message);
    }
  }

  async handleFileSubmit (event) {
    event.preventDefault();

    const input = this.fileForm.querySelector('#add-torrent-file-input');
    const file = input.files[0];
    const startPaused = this.isStartPaused(this.fileForm);

    if (!file) {
      this.showError('Please choose a .torrent file.');

      return;
    }

    try {
      await this.api.addTorrentFromFile(file, startPaused);
      this.modal.hide();
      this.onAdded();
    } catch (error) {
      this.showError(error.message);
    }
  }

  isStartPaused (form) {
    const checked = form.querySelector('input[type="radio"][name$="-start-mode"]:checked');

    return checked ? checked.value === 'paused' : false;
  }
}

export default AddTorrentModal;
