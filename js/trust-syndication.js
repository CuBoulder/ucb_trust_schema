// Trust Syndication Modal Web Component
class TrustSyndicationModal extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.isDragging = false;
    this.startX = 0;
    this.startY = 0;
    this.initialX = 0;
    this.initialY = 0;
    this.currentView = 'view'; // 'view' or 'edit'
  }

  connectedCallback() {
    this.render();
    this.attachEventListeners();
  }

  render() {
    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: none;
          position: fixed;
          inset: 0;
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 1000;
        }

        .modal-content {
          position: absolute;
          background-color: var(--trust-modal-bg, #fff);
          padding: var(--trust-spacing, 20px);
          width: 50%;
          max-width: 600px;
          max-height: 80vh;
          border-radius: var(--trust-modal-border-radius, 4px);
          box-shadow: var(--trust-modal-shadow, 0 2px 10px rgba(0, 0, 0, 0.1));
          overflow-y: auto;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          transition: transform 0.2s ease-out;
        }

        .modal-header {
          cursor: move;
          padding-bottom: var(--trust-spacing-sm, 10px);
          margin-bottom: var(--trust-spacing, 20px);
          border-bottom: 1px solid var(--trust-border-color, #ccc);
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .modal-title {
          margin: 0;
        }

        .syndication-status {
          font-size: 0.9em;
          padding: 4px 8px;
          border-radius: var(--trust-modal-border-radius, 4px);
          background-color: var(--trust-field-bg, #f5f5f5);
        }

        .syndication-status.enabled {
          background-color: #e6ffe6;
          color: #006600;
        }

        .syndication-status.disabled {
          background-color: #ffe6e6;
          color: #cc0000;
        }

        .form-item {
          margin-bottom: var(--trust-spacing, 20px);
        }

        .form-item label {
          display: block;
          margin-bottom: var(--trust-spacing-sm, 10px);
          font-weight: bold;
        }

        .form-item select,
        .form-item input {
          width: 100%;
          padding: 8px;
          border: 1px solid var(--trust-border-color, #ccc);
          border-radius: var(--trust-modal-border-radius, 4px);
        }

        .form-actions {
          margin-top: var(--trust-spacing, 20px);
          text-align: right;
        }

        .button {
          padding: 8px 16px;
          border: none;
          border-radius: var(--trust-modal-border-radius, 4px);
          cursor: pointer;
          margin-left: var(--trust-spacing-sm, 10px);
        }

        .button--primary {
          background-color: var(--trust-primary-color, #0071b8);
          color: white;
        }

        .button--secondary {
          background-color: var(--trust-secondary-color, #f5f5f5);
          color: var(--trust-text-color, #333);
          border: 1px solid var(--trust-border-color, #ccc);
        }

        .trust-syndication-field {
          margin-bottom: var(--trust-spacing, 20px);
          padding: var(--trust-spacing-sm, 10px);
          background-color: var(--trust-field-bg, #f5f5f5);
          border-radius: var(--trust-modal-border-radius, 4px);
        }

        .trust-syndication-field strong {
          display: inline-block;
          width: 150px;
          color: var(--trust-text-color, #333);
        }

        .view-content {
          display: none;
        }

        .edit-content {
          display: none;
        }

        .view-content.active,
        .edit-content.active {
          display: block;
        }

        @media (max-width: 768px) {
          .modal-content {
            width: 90%;
            max-width: none;
          }
          
          .trust-syndication-field strong {
            width: 120px;
          }
        }
      </style>

      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Trust Syndication</h2>
          <div class="syndication-status" id="syndication-status">Loading...</div>
        </div>
        
        <!-- View Content -->
        <div class="view-content">
          <div id="trust-syndication-data"></div>
          <div class="form-actions">
            <button class="button button--secondary" id="toggle-syndication">Toggle Syndication</button>
            <button class="button button--primary" id="edit-button">Edit</button>
            <button class="button button--secondary" id="close-button">Close</button>
          </div>
        </div>

        <!-- Edit Content -->
        <div class="edit-content">
          <form id="trust-syndication-form">
            <div class="form-item">
              <label for="trust-role">Trust Role</label>
              <select id="trust-role" name="trust_role" required>
                <option value="">Not Set</option>
                <option value="primary_source">Primary Source</option>
                <option value="secondary_source">Secondary Source</option>
                <option value="subject_matter_contributor">Subject Matter Contributor</option>
                <option value="unverified">Unverified</option>
              </select>
            </div>
            <div class="form-item">
              <label for="trust-scope">Trust Scope</label>
              <select id="trust-scope" name="trust_scope" required>
                <option value="">Not Set</option>
                <option value="department_level">Department-level</option>
                <option value="college_level">College-level</option>
                <option value="campus_wide">Campus-wide</option>
              </select>
            </div>
            <div class="form-item">
              <label for="trust-contact">Maintainer Contact</label>
              <input type="text" id="trust-contact" name="trust_contact" maxlength="255">
            </div>
            <div class="form-item">
              <label for="trust-topics">Trust Topics</label>
              <select id="trust-topics" name="trust_topics" multiple>
                <option value="math">Mathematics</option>
                <option value="science">Science</option>
                <option value="arts">Arts</option>
              </select>
              <div class="description">Hold Ctrl/Cmd to select multiple topics</div>
            </div>
            <div class="form-actions">
              <button type="submit" class="button button--primary">Save</button>
              <button type="button" class="button button--secondary" id="cancel-button">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    `;
  }

  attachEventListeners() {
    const modalContent = this.shadowRoot.querySelector('.modal-content');
    const header = this.shadowRoot.querySelector('.modal-header');
    const form = this.shadowRoot.querySelector('#trust-syndication-form');
    const cancelButton = this.shadowRoot.querySelector('#cancel-button');
    const closeButton = this.shadowRoot.querySelector('#close-button');
    const editButton = this.shadowRoot.querySelector('#edit-button');
    const toggleButton = this.shadowRoot.querySelector('#toggle-syndication');

    // Dragging functionality
    header.addEventListener('mousedown', this.startDragging.bind(this));
    document.addEventListener('mousemove', this.drag.bind(this));
    document.addEventListener('mouseup', this.stopDragging.bind(this));

    // Form submission
    form.addEventListener('submit', this.handleSubmit.bind(this));

    // Button handlers
    cancelButton.addEventListener('click', () => this.showView());
    closeButton.addEventListener('click', () => this.hide());
    editButton.addEventListener('click', () => this.showEdit());
    toggleButton.addEventListener('click', () => this.toggleSyndication());

    // Close on backdrop click only
    this.addEventListener('click', (e) => {
      if (e.target === this) {
        this.hide();
      }
    });

    // Prevent clicks inside the modal from closing it
    modalContent.addEventListener('click', (e) => {
      e.stopPropagation();
    });
  }

  showView() {
    this.currentView = 'view';
    this.shadowRoot.querySelector('.view-content').classList.add('active');
    this.shadowRoot.querySelector('.edit-content').classList.remove('active');
  }

  showEdit() {
    this.currentView = 'edit';
    this.shadowRoot.querySelector('.edit-content').classList.add('active');
    this.shadowRoot.querySelector('.view-content').classList.remove('active');
  }

  startDragging(e) {
    const modalContent = this.shadowRoot.querySelector('.modal-content');
    this.isDragging = true;
    this.startX = e.clientX;
    this.startY = e.clientY;
    this.initialX = modalContent.offsetLeft;
    this.initialY = modalContent.offsetTop;
    e.preventDefault();
  }

  drag(e) {
    if (!this.isDragging) return;

    const modalContent = this.shadowRoot.querySelector('.modal-content');
    const deltaX = e.clientX - this.startX;
    const deltaY = e.clientY - this.startY;

    modalContent.style.transform = 'none';
    modalContent.style.left = `${this.initialX + deltaX}px`;
    modalContent.style.top = `${this.initialY + deltaY}px`;
  }

  stopDragging() {
    this.isDragging = false;
  }

  async handleSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    // Add current syndication status
    const statusElement = this.shadowRoot.querySelector('#syndication-status');
    const isEnabled = statusElement.textContent.trim() === 'Enabled';
    formData.set('trust_syndication_enabled', isEnabled.toString());
    
    try {
      const dataToSend = Object.fromEntries(formData);
      dataToSend.trust_syndication_enabled = isEnabled;
      
      // Get selected trust topics
      const topicsSelect = form.querySelector('#trust-topics');
      dataToSend.trust_topics = Array.from(topicsSelect.selectedOptions).map(option => option.value);
      
      const response = await fetch(`/trust-syndication/save/${this.nodeId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': drupalSettings.csrfToken,
        },
        body: JSON.stringify(dataToSend),
      });

      if (!response.ok) {
        throw new Error('Failed to save trust metadata');
      }

      const data = await response.json();
      
      if (data.success) {
        this.currentData = {
          ...data.data,
          trust_syndication_enabled: isEnabled
        };
        this.updateViewContent(this.currentData);
        this.showView();
        
        // Update the overview table immediately
        const row = document.querySelector(`.trust-syndication-overview tbody tr[data-node-id="${this.nodeId}"]`);
        if (row) {
          const cells = row.querySelectorAll('td');
          if (cells.length >= 6) {
            cells[2].textContent = this.currentData.trust_role || '';
            cells[3].textContent = this.currentData.trust_scope || '';
            cells[4].textContent = this.currentData.trust_contact || '';
            const statusCell = cells[5];
            if (statusCell) {
              statusCell.textContent = this.currentData.trust_syndication_enabled ? 'Enabled' : 'Disabled';
            }
          }
        }
      } else {
        throw new Error(data.message || 'Failed to save trust metadata');
      }
    } catch (error) {
      this.showMessage(error.message, 'error');
    }
  }

  async toggleSyndication() {
    try {
      const url = Drupal.url(`trust-syndication/toggle/${this.nodeId}`);
      const csrfToken = await this.getCsrfToken();
      const statusElement = this.shadowRoot.querySelector('#syndication-status');
      const currentState = statusElement.textContent === 'Enabled';

      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        credentials: 'same-origin'
      });

      if (!response.ok) {
        throw new Error('Failed to toggle trust syndication');
      }

      const data = await response.json();
      
      if (data.success) {
        const newState = data.newState;
        statusElement.textContent = newState ? 'Enabled' : 'Disabled';
        statusElement.className = `syndication-status ${newState ? 'enabled' : 'disabled'}`;
        
        const toggleButton = this.shadowRoot.querySelector('#toggle-syndication');
        toggleButton.textContent = newState ? 'Disable Syndication' : 'Enable Syndication';
        
        this.currentData = {
          ...this.currentData,
          trust_syndication_enabled: newState
        };
        
        const row = document.querySelector(`.trust-syndication-overview tbody tr[data-node-id="${this.nodeId}"]`);
        if (row) {
          const statusCell = row.querySelector('td[data-drupal-selector="syndication-status"]');
          if (statusCell) {
            statusCell.textContent = newState ? 'Enabled' : 'Disabled';
          }
        }
        
        this.showMessage(data.message, 'success');
      } else {
        throw new Error(data.message || 'Failed to toggle trust syndication');
      }
    } catch (error) {
      this.showMessage(error.message, 'error');
    }
  }

  async getCsrfToken() {
    try {
      const response = await fetch(Drupal.url('session/token'));
      if (!response.ok) {
        throw new Error('Failed to get CSRF token');
      }
      return await response.text();
    } catch (error) {
      console.error('Error getting CSRF token:', error);
      throw error;
    }
  }

  showMessage(message, type = 'status') {
    const messageWrapper = document.createElement('div');
    messageWrapper.className = `messages messages--${type}`;
    messageWrapper.innerHTML = `<div class="messages__content">${message}</div>`;
    
    const modalContent = this.shadowRoot.querySelector('.modal-content');
    modalContent.insertAdjacentElement('afterbegin', messageWrapper);
    
    // Remove the message after 5 seconds
    setTimeout(() => {
      messageWrapper.remove();
    }, 5000);
  }

  show(nodeId) {
    this.nodeId = nodeId;
    this.style.display = 'block';
    this.loadData(nodeId);
  }

  hide() {
    if (this.currentView === 'view') {
      this.style.display = 'none';
      if (this.currentData) {
        this.dispatchEvent(new CustomEvent('trust-modal-closed', {
          detail: {
            nodeId: this.nodeId,
            data: this.currentData
          }
        }));
      }
    } else {
      if (confirm('You have unsaved changes. Are you sure you want to close?')) {
        this.showView();
        this.style.display = 'none';
      }
    }
  }

  async loadData(nodeId) {
    try {
      const response = await fetch(Drupal.url(`trust-syndication/view/${nodeId}`));
      if (!response.ok) throw new Error('Network response was not ok');
      
      const data = await response.json();
      
      if (data.success) {
        this.currentData = {
          ...data.data,
          trust_syndication_enabled: Boolean(data.data.trust_syndication_enabled)
        };
        this.updateViewContent(this.currentData);
        this.populateForm(this.currentData);
        this.showView();
        
        // Update syndication status
        const statusElement = this.shadowRoot.querySelector('#syndication-status');
        const toggleButton = this.shadowRoot.querySelector('#toggle-syndication');
        
        if (this.currentData.trust_syndication_enabled) {
          statusElement.textContent = 'Enabled';
          statusElement.className = 'syndication-status enabled';
          toggleButton.textContent = 'Disable Syndication';
        } else {
          statusElement.textContent = 'Disabled';
          statusElement.className = 'syndication-status disabled';
          toggleButton.textContent = 'Enable Syndication';
        }

        // Load trust topics
        await this.loadTrustTopics();
      } else {
        throw new Error(data.message || 'Failed to load trust metadata');
      }
    } catch (error) {
      this.showMessage(error.message, 'error');
    }
  }

  async loadTrustTopics() {
    try {
      const response = await fetch(Drupal.url('trust-syndication/topics'));
      if (!response.ok) throw new Error('Failed to load trust topics');
      
      const data = await response.json();
      if (data.success) {
        const topicsSelect = this.shadowRoot.querySelector('#trust-topics');
        topicsSelect.innerHTML = data.terms.map(term => 
          `<option value="${term.tid}">${term.name}</option>`
        ).join('');
        
        // Restore selected values if any
        if (this.currentData.trust_topics) {
          Array.from(topicsSelect.options).forEach(option => {
            option.selected = this.currentData.trust_topics.includes(option.value);
          });
        }
      }
    } catch (error) {
      this.showMessage('Failed to load trust topics', 'error');
    }
  }

  updateViewContent(data) {
    const viewContent = this.shadowRoot.querySelector('#trust-syndication-data');
    const topics = Array.isArray(data.trust_topics) ? data.trust_topics : [];
    
    viewContent.innerHTML = `
                      <div class="trust-syndication-field">
                        <strong>Trust Role:</strong> ${data.trust_role || 'Not set'}
                      </div>
                      <div class="trust-syndication-field">
                        <strong>Trust Scope:</strong> ${data.trust_scope || 'Not set'}
                      </div>
                      <div class="trust-syndication-field">
                        <strong>Maintainer Contact:</strong> ${data.trust_contact || 'Not set'}
                      </div>
                      <div class="trust-syndication-field">
                        <strong>Trust Topics:</strong> ${topics.length > 0 ? topics.join(', ') : 'Not set'}
                      </div>
                      <div class="trust-syndication-field">
                        <strong>Node Summary:</strong> ${data.node_summary || 'Not set'}
                      </div>
    `;
  }

  populateForm(data) {
    const form = this.shadowRoot.querySelector('#trust-syndication-form');
    form.querySelector('#trust-role').value = data.trust_role || '';
    form.querySelector('#trust-scope').value = data.trust_scope || '';
    form.querySelector('#trust-contact').value = data.trust_contact || '';
    
    // Trust topics will be populated in loadTrustTopics()
  }
}

// Register the custom element
customElements.define('trust-syndication-modal', TrustSyndicationModal);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeTrustSyndication);
} else {
  initializeTrustSyndication();
}

// Initialize the module
function initializeTrustSyndication() {
  const modal = document.createElement('trust-syndication-modal');
  document.body.appendChild(modal);

  document.querySelectorAll('.trust-syndication-button').forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const nodeId = button.dataset.nodeId;
      modal.show(nodeId);
    });
  });

  modal.addEventListener('trust-modal-closed', (e) => {
    const { nodeId, data } = e.detail;
    
    if (data) {
      const row = document.querySelector(`.trust-syndication-overview tbody tr[data-node-id="${nodeId}"]`);
      
      if (row) {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 6) {
          cells[2].textContent = data.trust_role || '';
          cells[3].textContent = data.trust_scope || '';
          cells[4].textContent = data.trust_contact || '';
          const statusCell = cells[5];
          if (statusCell) {
            statusCell.textContent = data.trust_syndication_enabled ? 'Enabled' : 'Disabled';
          }
        }
      } else if (window.location.pathname === '/admin/config/ucb-trust-schema') {
        window.location.reload();
      }
    }
  });

  document.querySelectorAll('.trust-syndication-toggle').forEach(button => {
    button.addEventListener('click', async (e) => {
      e.preventDefault();
      const url = button.getAttribute('href');
      
      try {
        const response = await fetch(url, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        
        if (data.success) {
          button.textContent = data.newState ? 'Disable Trust Syndication' : 'Enable Trust Syndication';
          
          const row = button.closest('tr');
          const statusCell = row.querySelector('td[data-drupal-selector="syndication-status"]');
          if (statusCell) {
            statusCell.textContent = data.newState ? 'Enabled' : 'Disabled';
          }
          
          const messageWrapper = document.createElement('div');
          messageWrapper.className = 'messages messages--status';
          messageWrapper.innerHTML = `<div class="messages__content">${data.message}</div>`;
          document.querySelector('.trust-syndication-overview').insertAdjacentElement('beforebegin', messageWrapper);
          
          setTimeout(() => {
            messageWrapper.remove();
          }, 5000);
        } else {
          throw new Error(data.message || 'Failed to toggle trust syndication');
        }
      } catch (error) {
        const messageWrapper = document.createElement('div');
        messageWrapper.className = 'messages messages--error';
        messageWrapper.innerHTML = `<div class="messages__content">${error.message}</div>`;
        document.querySelector('.trust-syndication-overview').insertAdjacentElement('beforebegin', messageWrapper);
        
        setTimeout(() => {
          messageWrapper.remove();
        }, 5000);
      }
    });
  });
} 