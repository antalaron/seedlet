import { formatBytes, formatRate, formatEta, formatDate, formatPercent } from './format.js';

/**
 * Handles the torrent details modal: read-only statistics, editable
 * per-torrent settings and the file selection/priority table.
 */
class TorrentDetailsModal {
  constructor ({ api, element, onChanged }) {
    this.api = api;
    this.element = element;
    this.onChanged = onChanged;
    this.id = null;
    this.files = [];

    this.titleLabel = element.querySelector('#torrent-details-modal-label');
    this.errorBox = element.querySelector('#details-error');
    this.progressBar = element.querySelector('#details-progress-bar');

    this.settingsForm = element.querySelector('#details-settings-form');
    this.priorityInput = element.querySelector('#details-priority');
    this.peerLimitInput = element.querySelector('#details-peer-limit');
    this.downloadDirInput = element.querySelector('#details-download-dir');
    this.seedRatioModeInput = element.querySelector('#details-seed-ratio-mode');
    this.seedRatioLimitInput = element.querySelector('#details-seed-ratio-limit');
    this.seedIdleModeInput = element.querySelector('#details-seed-idle-mode');
    this.seedIdleLimitInput = element.querySelector('#details-seed-idle-limit');

    this.filesForm = element.querySelector('#details-files-form');
    this.filesBody = element.querySelector('#details-files-body');
  }

  init () {
    this.settingsForm.addEventListener('submit', (event) => this.handleSettingsSubmit(event));
    this.filesForm.addEventListener('submit', (event) => this.handleFilesSubmit(event));
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

    try {
      const { torrent } = await this.api.getTorrent(id);
      this.render(torrent);
    } catch (error) {
      this.showError(error.message);
    }
  }

  render (torrent) {
    this.titleLabel.textContent = torrent.name;

    const percent = formatPercent(torrent.percentDone);
    this.progressBar.style.width = percent;
    this.progressBar.textContent = percent;

    this.element.querySelector('#details-status').textContent = torrent.statusLabel;
    this.element.querySelector('#details-size').textContent = formatBytes(torrent.totalSize);
    this.element.querySelector('#details-downloaded').textContent = formatBytes(torrent.downloadedEver);
    this.element.querySelector('#details-uploaded').textContent = formatBytes(torrent.uploadedEver);
    this.element.querySelector('#details-ratio').textContent = torrent.uploadRatio.toFixed(2);
    this.element.querySelector('#details-rate-download').textContent = formatRate(torrent.rateDownload);
    this.element.querySelector('#details-rate-upload').textContent = formatRate(torrent.rateUpload);
    this.element.querySelector('#details-eta').textContent = formatEta(torrent.eta);
    this.element.querySelector('#details-peers').textContent = `${torrent.seeders} seeders, ${torrent.leechers} leechers`;
    this.element.querySelector('#details-added').textContent = formatDate(torrent.addedDate);

    this.priorityInput.value = String(torrent.bandwidthPriority);
    this.peerLimitInput.value = torrent.peerLimit;
    this.downloadDirInput.value = torrent.downloadDir;
    this.seedRatioModeInput.value = String(torrent.seedRatioMode);
    this.seedRatioLimitInput.value = torrent.seedRatioLimit;
    this.seedIdleModeInput.value = String(torrent.seedIdleMode);
    this.seedIdleLimitInput.value = torrent.seedIdleLimit;

    this.renderFiles(torrent.files);
  }

  renderFiles (files) {
    this.files = files;
    this.filesBody.innerHTML = '';

    const template = document.getElementById('details-file-row-template');

    for (const file of files) {
      const fragment = template.content.cloneNode(true);
      const row = fragment.querySelector('tr');
      row.dataset.index = String(file.index);
      row.querySelector('.file-wanted-input').checked = file.wanted;
      row.querySelector('.file-name').textContent = file.name;
      row.querySelector('.file-name').title = file.name;
      row.querySelector('.file-size').textContent = formatBytes(file.length);
      row.querySelector('.file-progress').textContent = formatPercent(file.length > 0 ? file.bytesCompleted / file.length : 1);
      row.querySelector('.file-priority-input').value = String(file.priority);
      this.filesBody.appendChild(row);
    }
  }

  async handleSettingsSubmit (event) {
    event.preventDefault();
    this.showError(null);

    try {
      const { torrent } = await this.api.updateTorrent(this.id, {
        priority: Number(this.priorityInput.value),
        peerLimit: Number(this.peerLimitInput.value),
        downloadDir: this.downloadDirInput.value,
        seedRatioMode: Number(this.seedRatioModeInput.value),
        seedRatioLimit: Number(this.seedRatioLimitInput.value),
        seedIdleMode: Number(this.seedIdleModeInput.value),
        seedIdleLimit: Number(this.seedIdleLimitInput.value)
      });
      this.render(torrent);
      this.onChanged();
    } catch (error) {
      this.showError(error.message);
    }
  }

  async handleFilesSubmit (event) {
    event.preventDefault();
    this.showError(null);

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
      const { torrent } = await this.api.updateTorrentFiles(this.id, {
        wanted,
        unwanted,
        priorityHigh,
        priorityNormal,
        priorityLow
      });
      this.render(torrent);
      this.onChanged();
    } catch (error) {
      this.showError(error.message);
    }
  }
}

export default TorrentDetailsModal;
