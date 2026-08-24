import { formatBytes, formatRate, formatEta, formatDate, formatPercent } from './format.js';
import { getStatusPresentation, STATUS_CLASSES } from './torrentStatus.js';

const SAVE_FEEDBACK_RESET_MS = 1500;

/**
 * Handles the torrent details modal: read-only statistics, editable
 * per-torrent settings and the file selection/priority table.
 *
 * While open, it is refreshed by the torrent list's existing polling loop
 * (via {@link TorrentDetailsModal#refreshIfOpen}) instead of running a
 * second independent polling loop.
 */
class TorrentDetailsModal {
  constructor ({ api, element, onChanged }) {
    this.api = api;
    this.element = element;
    this.onChanged = onChanged;
    this.id = null;
    this.files = [];
    this.isOpen = false;
    this.savingSettings = false;
    this.savingFiles = false;

    this.titleLabel = element.querySelector('#torrent-details-modal-label');
    this.errorBox = element.querySelector('#details-error');
    this.progressBar = element.querySelector('#details-progress-bar');
    this.progressLabel = element.querySelector('#details-progress-label');

    this.settingsForm = element.querySelector('#details-settings-form');
    this.priorityInput = element.querySelector('#details-priority');
    this.peerLimitInput = element.querySelector('#details-peer-limit');
    this.downloadDirInput = element.querySelector('#details-download-dir');
    this.seedRatioModeInput = element.querySelector('#details-seed-ratio-mode');
    this.seedRatioLimitInput = element.querySelector('#details-seed-ratio-limit');
    this.seedIdleModeInput = element.querySelector('#details-seed-idle-mode');
    this.seedIdleLimitInput = element.querySelector('#details-seed-idle-limit');

    // Maps a settings field name to the input holding it and how to read its
    // value back out of a rendered torrent, so both render() and the save
    // handler can share a single source of truth.
    this.settingsFields = {
      priority: { input: this.priorityInput, read: (torrent) => String(torrent.bandwidthPriority) },
      peerLimit: { input: this.peerLimitInput, read: (torrent) => torrent.peerLimit },
      downloadDir: { input: this.downloadDirInput, read: (torrent) => torrent.downloadDir },
      seedRatioMode: { input: this.seedRatioModeInput, read: (torrent) => String(torrent.seedRatioMode) },
      seedRatioLimit: { input: this.seedRatioLimitInput, read: (torrent) => torrent.seedRatioLimit },
      seedIdleMode: { input: this.seedIdleModeInput, read: (torrent) => String(torrent.seedIdleMode) },
      seedIdleLimit: { input: this.seedIdleLimitInput, read: (torrent) => torrent.seedIdleLimit }
    };
    // Fields the user has started editing since the last render/save: these
    // are skipped by render() so live polling can't clobber in-progress edits.
    this.dirtySettingsFields = new Set();

    this.filesForm = element.querySelector('#details-files-form');
    this.filesBody = element.querySelector('#details-files-body');
    // Per-file-index edits (wanted/priority) the user has started making
    // since the last render/save, keyed by file index. File rows are fully
    // rebuilt on every render, so unlike settings inputs the edited value
    // itself (not just a flag) needs to be kept around to re-apply it.
    this.dirtyFiles = new Map();
  }

  init () {
    this.settingsForm.addEventListener('submit', (event) => this.handleSettingsSubmit(event));
    this.filesForm.addEventListener('submit', (event) => this.handleFilesSubmit(event));

    for (const [field, { input }] of Object.entries(this.settingsFields)) {
      input.addEventListener('input', () => this.dirtySettingsFields.add(field));
      input.addEventListener('change', () => this.dirtySettingsFields.add(field));
    }

    this.filesBody.addEventListener('change', (event) => this.handleFileFieldChange(event));

    // Track whether the modal is actually visible, so the shared torrent
    // list polling loop knows when it can stop refreshing this modal's data
    // (see refreshIfOpen()) instead of running a second polling loop.
    this.element.addEventListener('shown.bs.modal', () => {
      this.isOpen = true;
    });
    this.element.addEventListener('hidden.bs.modal', () => {
      this.isOpen = false;
      this.id = null;
    });
  }

  showError (message) {
    if (message) {
      this.errorBox.textContent = message;
      this.errorBox.classList.remove('d-none');
    } else {
      this.errorBox.classList.add('d-none');
    }
  }

  async open (id) {
    this.id = id;
    this.showError(null);
    this.dirtySettingsFields.clear();
    this.dirtyFiles.clear();

    try {
      const { torrent } = await this.api.getTorrent(id);
      this.render(torrent);
    } catch (error) {
      this.showError(error.message);
    }
  }

  /**
   * Called by the torrent list after every poll/refresh so this modal's
   * data stays live while it is open, without a second independent polling
   * loop. `torrents` is the current torrent-list summary payload, used only
   * to detect the torrent having been removed; the modal's own (richer)
   * data is re-fetched with a single extra request.
   */
  async refreshIfOpen (torrents) {
    if (!this.isOpen || this.id === null || this.savingSettings || this.savingFiles) {
      return;
    }

    if (!torrents.some((torrent) => torrent.id === this.id)) {
      this.showError('This torrent no longer exists. It may have been removed elsewhere.');
      this.id = null;

      return;
    }

    try {
      const { torrent } = await this.api.getTorrent(this.id);
      this.render(torrent);
      this.showError(null);
    } catch (error) {
      this.showError(error.message);
    }
  }

  render (torrent) {
    this.titleLabel.textContent = torrent.name;

    const presentation = getStatusPresentation(torrent);
    this.progressBar.classList.remove(...STATUS_CLASSES);
    this.progressBar.classList.add(presentation.progressClass);

    const percent = formatPercent(torrent.percentDone);
    this.progressBar.style.width = percent;
    this.progressLabel.textContent = percent;

    this.element.querySelector('#details-status').innerHTML = `<i class="fa-solid ${presentation.icon}" aria-hidden="true"></i> ${torrent.statusLabel}`;
    this.element.querySelector('#details-size').textContent = formatBytes(torrent.totalSize);
    this.element.querySelector('#details-downloaded').textContent = formatBytes(torrent.downloadedEver);
    this.element.querySelector('#details-uploaded').textContent = formatBytes(torrent.uploadedEver);
    this.element.querySelector('#details-ratio').textContent = torrent.uploadRatio.toFixed(2);
    this.element.querySelector('#details-rate-download').textContent = formatRate(torrent.rateDownload);
    this.element.querySelector('#details-rate-upload').textContent = formatRate(torrent.rateUpload);
    this.element.querySelector('#details-eta').textContent = formatEta(torrent.eta);
    this.element.querySelector('#details-peers').textContent = `${torrent.seeders} seeders, ${torrent.leechers} leechers`;
    this.element.querySelector('#details-added').textContent = formatDate(torrent.addedDate);

    for (const [field, { input, read }] of Object.entries(this.settingsFields)) {
      if (!this.dirtySettingsFields.has(field)) {
        input.value = read(torrent);
      }
    }

    this.renderFiles(torrent.files);
  }

  renderFiles (files) {
    this.files = files;
    this.filesBody.innerHTML = '';

    const template = document.getElementById('details-file-row-template');

    for (const file of files) {
      const dirty = this.dirtyFiles.get(file.index);
      const fragment = template.content.cloneNode(true);
      const row = fragment.querySelector('tr');
      row.dataset.index = String(file.index);
      row.querySelector('.file-wanted-input').checked = dirty && dirty.wanted !== undefined ? dirty.wanted : file.wanted;
      row.querySelector('.file-name').textContent = file.name;
      row.querySelector('.file-name').title = file.name;
      row.querySelector('.file-size').textContent = formatBytes(file.length);
      row.querySelector('.file-progress').textContent = formatPercent(file.length > 0 ? file.bytesCompleted / file.length : 1);
      row.querySelector('.file-priority-input').value = String(dirty && dirty.priority !== undefined ? dirty.priority : file.priority);
      this.filesBody.appendChild(row);
    }
  }

  /**
   * Marks an individual file row field ("wanted" checkbox or priority
   * select) dirty so the next render() re-applies the user's edited value
   * instead of the server's, without disabling polling for the rest of
   * the modal.
   */
  handleFileFieldChange (event) {
    const row = event.target.closest('tr[data-index]');

    if (!row) {
      return;
    }

    const index = Number(row.dataset.index);
    const dirty = this.dirtyFiles.get(index) || {};

    if (event.target.classList.contains('file-wanted-input')) {
      dirty.wanted = event.target.checked;
    } else if (event.target.classList.contains('file-priority-input')) {
      dirty.priority = Number(event.target.value);
    } else {
      return;
    }

    this.dirtyFiles.set(index, dirty);
  }

  async handleSettingsSubmit (event) {
    event.preventDefault();

    if (this.savingSettings) {
      return;
    }

    this.showError(null);
    this.savingSettings = true;
    const button = this.settingsForm.querySelector('button[type="submit"]');

    try {
      await this.withSaveFeedback(button, 'Save settings', async () => {
        const { torrent } = await this.api.updateTorrent(this.id, {
          priority: Number(this.priorityInput.value),
          peerLimit: Number(this.peerLimitInput.value),
          downloadDir: this.downloadDirInput.value,
          seedRatioMode: Number(this.seedRatioModeInput.value),
          seedRatioLimit: Number(this.seedRatioLimitInput.value),
          seedIdleMode: Number(this.seedIdleModeInput.value),
          seedIdleLimit: Number(this.seedIdleLimitInput.value)
        });
        this.dirtySettingsFields.clear();
        this.render(torrent);
        this.onChanged();
      });
    } catch (error) {
      this.showError(error.message);
    } finally {
      this.savingSettings = false;
    }
  }

  async handleFilesSubmit (event) {
    event.preventDefault();

    if (this.savingFiles) {
      return;
    }

    this.showError(null);
    this.savingFiles = true;
    const button = this.filesForm.querySelector('button[type="submit"]');

    const wanted = [];
    const unwanted = [];
    const priorityHigh = [];
    const priorityNormal = [];
    const priorityLow = [];

    for (const row of this.filesBody.querySelectorAll('tr')) {
      const index = Number(row.dataset.index);
      const isWanted = row.querySelector('.file-wanted-input').checked;
      const priority = Number(row.querySelector('.file-priority-input').value);

      (isWanted ? wanted : unwanted).push(index);

      if (priority === 1) {
        priorityHigh.push(index);
      } else if (priority === -1) {
        priorityLow.push(index);
      } else {
        priorityNormal.push(index);
      }
    }

    try {
      await this.withSaveFeedback(button, 'Save file selection', async () => {
        const { torrent } = await this.api.updateTorrentFiles(this.id, {
          wanted,
          unwanted,
          priorityHigh,
          priorityNormal,
          priorityLow
        });
        this.dirtyFiles.clear();
        this.render(torrent);
        this.onChanged();
      });
    } catch (error) {
      this.showError(error.message);
    } finally {
      this.savingFiles = false;
    }
  }

  /**
   * Gives a save button immediate "in progress" feedback (spinner,
   * disabled, so a slow tap/double submit can't fire the request twice),
   * then a brief success/error indication, before restoring its normal
   * label. The underlying `action` is still responsible for surfacing
   * errors via {@link TorrentDetailsModal#showError} - a failed save never
   * touches the form fields, so the user's changes are never discarded.
   */
  async withSaveFeedback (button, label, action) {
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving…';

    try {
      await action();
      button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Saved';
    } catch (error) {
      button.innerHTML = '<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Save failed';
      throw error;
    } finally {
      window.setTimeout(() => {
        button.disabled = false;
        button.innerHTML = `<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> ${label}`;
      }, SAVE_FEEDBACK_RESET_MS);
    }
  }
}

export default TorrentDetailsModal;
