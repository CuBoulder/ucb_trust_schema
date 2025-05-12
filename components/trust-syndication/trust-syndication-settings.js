class TrustSyndicationSettings extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
    this.setupEventListeners();
  }

  static get observedAttributes() {
    return ['node-id', 'trust-role', 'trust-scope', 'trust-contact', 'trust-topics'];
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (oldValue !== newValue) {
      this.render();
    }
  }

  render() {
    const nodeId = this.getAttribute('node-id');
    const trustRole = this.getAttribute('trust-role');
    const trustScope = this.getAttribute('trust-scope');
    const trustContact = this.getAttribute('trust-contact');
    const trustTopics = JSON.parse(this.getAttribute('trust-topics') || '[]');

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          padding: 1rem;
          border: 1px solid #ccc;
          border-radius: 4px;
          margin: 1rem 0;
        }
        .form-group {
          margin-bottom: 1rem;
        }
        label {
          display: block;
          margin-bottom: 0.5rem;
          font-weight: bold;
        }
        select, input {
          width: 100%;
          padding: 0.5rem;
          border: 1px solid #ccc;
          border-radius: 4px;
        }
        button {
          background: #0071b8;
          color: white;
          border: none;
          padding: 0.5rem 1rem;
          border-radius: 4px;
          cursor: pointer;
        }
        button:hover {
          background: #005a94;
        }
      </style>
      <div class="trust-syndication">
        <h3>Trust Syndication Settings</h3>
        <div class="form-group">
          <label for="trust-role">Trust Role</label>
          <select id="trust-role">
            <option value="primary_source" ${trustRole === 'primary_source' ? 'selected' : ''}>Primary Source</option>
            <option value="secondary_source" ${trustRole === 'secondary_source' ? 'selected' : ''}>Secondary Source</option>
            <option value="subject_matter_contributor" ${trustRole === 'subject_matter_contributor' ? 'selected' : ''}>Subject Matter Contributor</option>
            <option value="unverified" ${trustRole === 'unverified' ? 'selected' : ''}>Unverified</option>
          </select>
        </div>
        <div class="form-group">
          <label for="trust-scope">Trust Scope</label>
          <select id="trust-scope">
            <option value="department_level" ${trustScope === 'department_level' ? 'selected' : ''}>Department-level</option>
            <option value="college_level" ${trustScope === 'college_level' ? 'selected' : ''}>College-level</option>
            <option value="campus_wide" ${trustScope === 'campus_wide' ? 'selected' : ''}>Campus-wide</option>
          </select>
        </div>
        <div class="form-group">
          <label for="trust-contact">Maintainer Contact</label>
          <input type="text" id="trust-contact" value="${trustContact || ''}" placeholder="Enter contact information">
        </div>
        <div class="form-group">
          <label for="trust-topics">Trust Topics</label>
          <input type="text" id="trust-topics" value="${trustTopics.join(', ')}" placeholder="Enter topics (comma-separated)">
        </div>
        <button id="save-trust">Save Trust Settings</button>
      </div>
    `;
  }

  setupEventListeners() {
    const saveButton = this.shadowRoot.getElementById('save-trust');
    saveButton.addEventListener('click', () => this.saveTrustSettings());
  }

  async saveTrustSettings() {
    const nodeId = this.getAttribute('node-id');
    const trustRole = this.shadowRoot.getElementById('trust-role').value;
    const trustScope = this.shadowRoot.getElementById('trust-scope').value;
    const trustContact = this.shadowRoot.getElementById('trust-contact').value;
    const trustTopics = this.shadowRoot.getElementById('trust-topics').value
      .split(',')
      .map(topic => topic.trim())
      .filter(topic => topic);

    try {
      const response = await fetch(`/api/trust-schema/${nodeId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': drupalSettings.csrfToken,
        },
        body: JSON.stringify({
          trust_role: trustRole,
          trust_scope: trustScope,
          trust_contact: trustContact,
          trust_topics: trustTopics,
        }),
      });

      if (!response.ok) {
        throw new Error('Failed to save trust settings');
      }

      // Dispatch success event
      this.dispatchEvent(new CustomEvent('trust-saved', {
        detail: {
          nodeId,
          trustRole,
          trustScope,
          trustContact,
          trustTopics,
        },
      }));
    } catch (error) {
      console.error('Error saving trust settings:', error);
      // Dispatch error event
      this.dispatchEvent(new CustomEvent('trust-error', {
        detail: { error: error.message },
      }));
    }
  }
}

customElements.define('trust-syndication-settings', TrustSyndicationSettings); 