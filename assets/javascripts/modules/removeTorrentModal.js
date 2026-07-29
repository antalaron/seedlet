import { Modal } from 'bootstrap';

/**
 * Handles the removal confirmation modal, offering an explicit,
 * touch-friendly choice between keeping or deleting downloaded data.
 */
class RemoveTorrentModal {
  constructor ({ api, element, onRemoved }) {
    this.api = api;
    this.element = element;
    this.onRemoved = onRemoved;
    this.modal = Modal.getOrCreateInstance(element);
    this.nameLabel = element.querySelector('#remove-torrent-name');
    this.errorBox = element.querySelector('#remove-torrent-error');
    this.keepDataButton = element.querySelector('#remove-torrent-keep-data-btn');
    this.deleteDataButton = element.querySelector('#remove-torrent-delete-data-btn');
    this.id = null;
  }

  init () {
    this.keepDataButton.addEventListener('click', () => this.remove(false));
    this.deleteDataButton.addEventListener('click', () => this.remove(true));
    this.element.addEventListener('hidden.bs.modal', () => this.showError(null));
  }

  open (id, name) {
    this.id = id;
    this.nameLabel.textContent = name;
    this.showError(null);
    this.modal.show();
  }

  showError (message) {
    if (message) {
      this.errorBox.textContent = message;
      this.errorBox.classList.remove('d-none');
    } else {
      this.errorBox.classList.add('d-none');
    }
  }

  async remove (deleteLocalData) {
    try {
      await this.api.removeTorrent(this.id, deleteLocalData);
      this.modal.hide();
      this.onRemoved();
    } catch (error) {
      this.showError(error.message);
    }
  }
}

export default RemoveTorrentModal;
