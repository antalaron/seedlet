import { Modal } from 'bootstrap';
import Api from './api.js';
import SortPreference from './sortPreference.js';
import TorrentList from './torrentList.js';
import AddTorrentModal from './addTorrentModal.js';
import RemoveTorrentModal from './removeTorrentModal.js';
import TorrentDetailsModal from './torrentDetailsModal.js';
import SettingsModal from './settingsModal.js';

/**
 * Wires together the API client, the torrent list and the modals that
 * make up the Seedlet application.
 */
class Seedlet {
  /**
   * Initialize the app
   */
  run () {
    const app = document.getElementById('app');
    if (!app) {
      return;
    }

    const api = new Api(app.dataset.csrfToken);

    const detailsModalElement = document.getElementById('torrent-details-modal');
    const detailsModal = new TorrentDetailsModal({
      api,
      element: detailsModalElement,
      onChanged: () => this.torrentList.refresh()
    });
    detailsModal.init();
    const detailsModalInstance = Modal.getOrCreateInstance(detailsModalElement);

    const removeModal = new RemoveTorrentModal({
      api,
      element: document.getElementById('remove-torrent-modal'),
      onRemoved: () => this.torrentList.refresh()
    });
    removeModal.init();

    const addModal = new AddTorrentModal({
      api,
      element: document.getElementById('add-torrent-modal'),
      onAdded: () => this.torrentList.refresh()
    });
    addModal.init();

    const settingsModal = new SettingsModal({
      api,
      element: document.getElementById('settings-modal'),
      onSessionChanged: (session) => this.updateTurtleButton(session)
    });
    settingsModal.init();

    this.torrentList = new TorrentList({
      api,
      sortPreference: new SortPreference(),
      container: document.getElementById('torrent-list'),
      emptyState: document.getElementById('empty-state'),
      countLabel: document.getElementById('torrent-count'),
      sortFieldSelect: document.getElementById('sort-field'),
      sortDirectionButton: document.getElementById('sort-direction-btn'),
      errorBanner: document.getElementById('error-banner'),
      onDetails: (id) => {
        detailsModalInstance.show();
        detailsModal.open(id);
      },
      onRemove: (id, name) => removeModal.open(id, name)
    });
    this.torrentList.init();

    this.api = api;
    this.turtleButton = document.getElementById('turtle-toggle-btn');
    this.turtleButton.addEventListener('click', () => this.toggleTurtleMode());
    this.loadTurtleState();
  }

  async loadTurtleState () {
    try {
      const { session } = await this.api.getSession();
      this.updateTurtleButton(session);
    } catch {
      // The main polling loop already surfaces connectivity errors.
    }
  }

  async toggleTurtleMode () {
    try {
      const { session } = await this.api.getSession();
      const updated = await this.api.updateSession({ altSpeedEnabled: !session.altSpeedEnabled });
      this.updateTurtleButton(updated.session);
    } catch (error) {
      this.torrentList.showError(error.message);
    }
  }

  updateTurtleButton (session) {
    this.turtleButton.classList.toggle('btn-warning', session.altSpeedEnabled);
    this.turtleButton.classList.toggle('btn-outline-light', !session.altSpeedEnabled);
    this.turtleButton.setAttribute('aria-pressed', String(session.altSpeedEnabled));
  }
}

export default Seedlet;
