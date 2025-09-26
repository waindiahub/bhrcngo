<template>
  <div class="member-settings-page">
    <!-- Header Section -->
    <div class="settings-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="header-content">
              <h1 class="page-title">
                <i class="fas fa-cog me-3"></i>
                Account Settings
              </h1>
              <p class="page-subtitle">
                Manage your account preferences, security settings, and privacy options
              </p>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="header-actions">
              <button 
                class="btn btn-outline-light me-2"
                @click="exportSettings"
                :disabled="exporting"
              >
                <i class="fas me-2" :class="exporting ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                Export Settings
              </button>
              <button 
                class="btn btn-light"
                @click="resetToDefaults"
              >
                <i class="fas fa-undo me-2"></i>
                Reset to Defaults
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Settings Content -->
    <div class="settings-content">
      <div class="container">
        <div class="row">
          <!-- Settings Navigation -->
          <div class="col-lg-3">
            <div class="settings-nav">
              <div class="nav-header">
                <h6>Settings Categories</h6>
              </div>
              <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeSection === 'profile' }"
                    @click="switchSection('profile')"
                  >
                    <i class="fas fa-user me-2"></i>
                    Profile Settings
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeSection === 'security' }"
                    @click="switchSection('security')"
                  >
                    <i class="fas fa-shield-alt me-2"></i>
                    Security & Privacy
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeSection === 'notifications' }"
                    @click="switchSection('notifications')"
                  >
                    <i class="fas fa-bell me-2"></i>
                    Notifications
                    <span v-if="hasNotificationChanges" class="change-indicator"></span>
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeSection === 'preferences' }"
                    @click="switchSection('preferences')"
                  >
                    <i class="fas fa-palette me-2"></i>
                    Preferences
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeSection === 'privacy' }"
                    @click="switchSection('privacy')"
                  >
                    <i class="fas fa-lock me-2"></i>
                    Privacy Controls
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeSection === 'data' }"
                    @click="switchSection('data')"
                  >
                    <i class="fas fa-database me-2"></i>
                    Data Management
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeSection === 'account' }"
                    @click="switchSection('account')"
                  >
                    <i class="fas fa-user-cog me-2"></i>
                    Account Management
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <!-- Settings Content -->
          <div class="col-lg-9">
            <!-- Profile Settings -->
            <div v-if="activeSection === 'profile'" class="settings-section">
              <div class="section-header">
                <h3>Profile Settings</h3>
                <p>Update your personal information and profile preferences</p>
              </div>

              <div class="settings-cards">
                <!-- Basic Information -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-user me-2"></i>Basic Information</h5>
                  </div>
                  <div class="card-body">
                    <form @submit.prevent="updateProfile">
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">First Name *</label>
                          <input 
                            type="text" 
                            class="form-control" 
                            v-model="profileSettings.firstName"
                            required
                          >
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Last Name *</label>
                          <input 
                            type="text" 
                            class="form-control" 
                            v-model="profileSettings.lastName"
                            required
                          >
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Email Address *</label>
                          <input 
                            type="email" 
                            class="form-control" 
                            v-model="profileSettings.email"
                            required
                          >
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Phone Number</label>
                          <input 
                            type="tel" 
                            class="form-control" 
                            v-model="profileSettings.phone"
                          >
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea 
                          class="form-control" 
                          rows="3"
                          v-model="profileSettings.bio"
                          placeholder="Tell us about yourself..."
                        ></textarea>
                      </div>

                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Date of Birth</label>
                          <input 
                            type="date" 
                            class="form-control" 
                            v-model="profileSettings.dateOfBirth"
                          >
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label">Gender</label>
                          <select class="form-select" v-model="profileSettings.gender">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                            <option value="prefer_not_to_say">Prefer not to say</option>
                          </select>
                        </div>
                      </div>

                      <div class="d-flex justify-content-end">
                        <button 
                          type="submit" 
                          class="btn btn-primary"
                          :disabled="updating"
                        >
                          <i class="fas me-2" :class="updating ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                          {{ updating ? 'Updating...' : 'Update Profile' }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Profile Picture -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-camera me-2"></i>Profile Picture</h5>
                  </div>
                  <div class="card-body">
                    <div class="profile-picture-section">
                      <div class="current-picture">
                        <img 
                          :src="profileSettings.avatar || '/images/default-avatar.png'" 
                          :alt="profileSettings.firstName + ' ' + profileSettings.lastName"
                          class="profile-img"
                        >
                      </div>
                      <div class="picture-actions">
                        <input 
                          type="file" 
                          ref="avatarInput" 
                          accept="image/*" 
                          @change="handleAvatarUpload"
                          style="display: none"
                        >
                        <button 
                          class="btn btn-outline-primary me-2"
                          @click="$refs.avatarInput.click()"
                        >
                          <i class="fas fa-upload me-2"></i>
                          Upload New Picture
                        </button>
                        <button 
                          v-if="profileSettings.avatar"
                          class="btn btn-outline-danger"
                          @click="removeAvatar"
                        >
                          <i class="fas fa-trash me-2"></i>
                          Remove Picture
                        </button>
                      </div>
                    </div>
                    <small class="text-muted">
                      Recommended: Square image, at least 200x200 pixels. Max file size: 5MB.
                    </small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Security & Privacy -->
            <div v-if="activeSection === 'security'" class="settings-section">
              <div class="section-header">
                <h3>Security & Privacy</h3>
                <p>Manage your account security and privacy settings</p>
              </div>

              <div class="settings-cards">
                <!-- Password Settings -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-key me-2"></i>Password Settings</h5>
                  </div>
                  <div class="card-body">
                    <form @submit.prevent="changePassword">
                      <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <div class="input-group">
                          <input 
                            :type="showCurrentPassword ? 'text' : 'password'" 
                            class="form-control" 
                            v-model="passwordForm.currentPassword"
                            required
                          >
                          <button 
                            type="button" 
                            class="btn btn-outline-secondary"
                            @click="showCurrentPassword = !showCurrentPassword"
                          >
                            <i class="fas" :class="showCurrentPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                          </button>
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <div class="input-group">
                          <input 
                            :type="showNewPassword ? 'text' : 'password'" 
                            class="form-control" 
                            v-model="passwordForm.newPassword"
                            required
                          >
                          <button 
                            type="button" 
                            class="btn btn-outline-secondary"
                            @click="showNewPassword = !showNewPassword"
                          >
                            <i class="fas" :class="showNewPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                          </button>
                        </div>
                        <div class="password-strength">
                          <div class="strength-bar">
                            <div 
                              class="strength-fill" 
                              :class="passwordStrength.class"
                              :style="{ width: passwordStrength.width }"
                            ></div>
                          </div>
                          <small class="text-muted">{{ passwordStrength.text }}</small>
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <input 
                          type="password" 
                          class="form-control" 
                          v-model="passwordForm.confirmPassword"
                          required
                        >
                      </div>

                      <div class="d-flex justify-content-end">
                        <button 
                          type="submit" 
                          class="btn btn-primary"
                          :disabled="changingPassword || !isPasswordValid"
                        >
                          <i class="fas me-2" :class="changingPassword ? 'fa-spinner fa-spin' : 'fa-key'"></i>
                          {{ changingPassword ? 'Changing...' : 'Change Password' }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Two-Factor Authentication -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-shield-alt me-2"></i>Two-Factor Authentication</h5>
                  </div>
                  <div class="card-body">
                    <div class="tfa-status">
                      <div class="status-indicator">
                        <i class="fas" :class="securitySettings.twoFactorEnabled ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'"></i>
                        <span class="ms-2">
                          Two-Factor Authentication is 
                          <strong>{{ securitySettings.twoFactorEnabled ? 'Enabled' : 'Disabled' }}</strong>
                        </span>
                      </div>
                      
                      <div class="tfa-actions mt-3">
                        <button 
                          v-if="!securitySettings.twoFactorEnabled"
                          class="btn btn-success"
                          @click="enableTwoFactor"
                        >
                          <i class="fas fa-plus me-2"></i>
                          Enable 2FA
                        </button>
                        <button 
                          v-else
                          class="btn btn-outline-danger"
                          @click="disableTwoFactor"
                        >
                          <i class="fas fa-minus me-2"></i>
                          Disable 2FA
                        </button>
                      </div>
                    </div>

                    <div v-if="securitySettings.twoFactorEnabled" class="backup-codes mt-3">
                      <h6>Backup Codes</h6>
                      <p class="text-muted">Save these backup codes in a safe place. You can use them to access your account if you lose your phone.</p>
                      <button class="btn btn-outline-primary btn-sm" @click="generateBackupCodes">
                        <i class="fas fa-refresh me-2"></i>
                        Generate New Backup Codes
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Active Sessions -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-desktop me-2"></i>Active Sessions</h5>
                  </div>
                  <div class="card-body">
                    <div class="sessions-list">
                      <div 
                        v-for="session in activeSessions" 
                        :key="session.id"
                        class="session-item"
                      >
                        <div class="session-info">
                          <div class="session-device">
                            <i class="fas" :class="getDeviceIcon(session.device_type)"></i>
                            <strong>{{ session.device_name }}</strong>
                            <span v-if="session.is_current" class="badge bg-success ms-2">Current</span>
                          </div>
                          <div class="session-details">
                            <small class="text-muted">
                              {{ session.location }} • Last active: {{ formatTime(session.last_active) }}
                            </small>
                          </div>
                        </div>
                        <div class="session-actions">
                          <button 
                            v-if="!session.is_current"
                            class="btn btn-outline-danger btn-sm"
                            @click="terminateSession(session.id)"
                          >
                            <i class="fas fa-sign-out-alt me-1"></i>
                            End Session
                          </button>
                        </div>
                      </div>
                    </div>
                    
                    <div class="mt-3">
                      <button 
                        class="btn btn-outline-warning"
                        @click="terminateAllSessions"
                      >
                        <i class="fas fa-sign-out-alt me-2"></i>
                        End All Other Sessions
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notifications -->
            <div v-if="activeSection === 'notifications'" class="settings-section">
              <div class="section-header">
                <h3>Notification Settings</h3>
                <p>Choose how and when you want to receive notifications</p>
              </div>

              <div class="settings-cards">
                <!-- Email Notifications -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-envelope me-2"></i>Email Notifications</h5>
                  </div>
                  <div class="card-body">
                    <div class="notification-options">
                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="emailEvents"
                            v-model="notificationSettings.email.events"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="emailEvents">
                            <strong>Event Updates</strong>
                            <small class="d-block text-muted">Notifications about upcoming events and registrations</small>
                          </label>
                        </div>
                      </div>

                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="emailComplaints"
                            v-model="notificationSettings.email.complaints"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="emailComplaints">
                            <strong>Complaint Updates</strong>
                            <small class="d-block text-muted">Status updates on your complaints and resolutions</small>
                          </label>
                        </div>
                      </div>

                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="emailDonations"
                            v-model="notificationSettings.email.donations"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="emailDonations">
                            <strong>Donation Receipts</strong>
                            <small class="d-block text-muted">Receipts and acknowledgments for your donations</small>
                          </label>
                        </div>
                      </div>

                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="emailCertificates"
                            v-model="notificationSettings.email.certificates"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="emailCertificates">
                            <strong>Certificate Updates</strong>
                            <small class="d-block text-muted">Notifications when certificates are ready for download</small>
                          </label>
                        </div>
                      </div>

                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="emailNewsletter"
                            v-model="notificationSettings.email.newsletter"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="emailNewsletter">
                            <strong>Newsletter & Updates</strong>
                            <small class="d-block text-muted">Monthly newsletter and important announcements</small>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- SMS Notifications -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-sms me-2"></i>SMS Notifications</h5>
                  </div>
                  <div class="card-body">
                    <div class="notification-options">
                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="smsUrgent"
                            v-model="notificationSettings.sms.urgent"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="smsUrgent">
                            <strong>Urgent Notifications</strong>
                            <small class="d-block text-muted">Critical updates and emergency notifications</small>
                          </label>
                        </div>
                      </div>

                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="smsEvents"
                            v-model="notificationSettings.sms.events"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="smsEvents">
                            <strong>Event Reminders</strong>
                            <small class="d-block text-muted">Reminders for upcoming events you're registered for</small>
                          </label>
                        </div>
                      </div>

                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="smsComplaints"
                            v-model="notificationSettings.sms.complaints"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="smsComplaints">
                            <strong>Complaint Status</strong>
                            <small class="d-block text-muted">Important updates on your complaint status</small>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Push Notifications -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-bell me-2"></i>Push Notifications</h5>
                  </div>
                  <div class="card-body">
                    <div class="push-status mb-3">
                      <div class="status-indicator">
                        <i class="fas" :class="pushNotificationsEnabled ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'"></i>
                        <span class="ms-2">
                          Push notifications are 
                          <strong>{{ pushNotificationsEnabled ? 'Enabled' : 'Disabled' }}</strong>
                        </span>
                      </div>
                      
                      <button 
                        v-if="!pushNotificationsEnabled"
                        class="btn btn-primary btn-sm mt-2"
                        @click="enablePushNotifications"
                      >
                        <i class="fas fa-bell me-2"></i>
                        Enable Push Notifications
                      </button>
                    </div>

                    <div v-if="pushNotificationsEnabled" class="notification-options">
                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="pushEvents"
                            v-model="notificationSettings.push.events"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="pushEvents">
                            <strong>Event Updates</strong>
                          </label>
                        </div>
                      </div>

                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="pushComplaints"
                            v-model="notificationSettings.push.complaints"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="pushComplaints">
                            <strong>Complaint Updates</strong>
                          </label>
                        </div>
                      </div>

                      <div class="notification-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="pushCertificates"
                            v-model="notificationSettings.push.certificates"
                            @change="updateNotifications"
                          >
                          <label class="form-check-label" for="pushCertificates">
                            <strong>Certificate Updates</strong>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Preferences -->
            <div v-if="activeSection === 'preferences'" class="settings-section">
              <div class="section-header">
                <h3>Preferences</h3>
                <p>Customize your experience and interface preferences</p>
              </div>

              <div class="settings-cards">
                <!-- Display Preferences -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-palette me-2"></i>Display Preferences</h5>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Theme</label>
                        <select 
                          class="form-select" 
                          v-model="preferences.theme"
                          @change="updatePreferences"
                        >
                          <option value="light">Light</option>
                          <option value="dark">Dark</option>
                          <option value="auto">Auto (System)</option>
                        </select>
                      </div>
                      
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Language</label>
                        <select 
                          class="form-select" 
                          v-model="preferences.language"
                          @change="updatePreferences"
                        >
                          <option value="en">English</option>
                          <option value="hi">हिंदी (Hindi)</option>
                          <option value="bn">বাংলা (Bengali)</option>
                          <option value="te">తెలుగు (Telugu)</option>
                          <option value="ta">தமிழ் (Tamil)</option>
                        </select>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Date Format</label>
                        <select 
                          class="form-select" 
                          v-model="preferences.dateFormat"
                          @change="updatePreferences"
                        >
                          <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                          <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                          <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                          <option value="DD MMM YYYY">DD MMM YYYY</option>
                        </select>
                      </div>
                      
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Time Format</label>
                        <select 
                          class="form-select" 
                          v-model="preferences.timeFormat"
                          @change="updatePreferences"
                        >
                          <option value="12">12 Hour (AM/PM)</option>
                          <option value="24">24 Hour</option>
                        </select>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Items Per Page</label>
                      <select 
                        class="form-select" 
                        v-model="preferences.itemsPerPage"
                        @change="updatePreferences"
                      >
                        <option value="10">10 items</option>
                        <option value="25">25 items</option>
                        <option value="50">50 items</option>
                        <option value="100">100 items</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Dashboard Preferences -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-tachometer-alt me-2"></i>Dashboard Preferences</h5>
                  </div>
                  <div class="card-body">
                    <div class="dashboard-widgets">
                      <h6>Visible Dashboard Widgets</h6>
                      <p class="text-muted">Choose which widgets to display on your dashboard</p>
                      
                      <div class="widget-options">
                        <div class="form-check">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="widgetEvents"
                            v-model="preferences.dashboardWidgets.events"
                            @change="updatePreferences"
                          >
                          <label class="form-check-label" for="widgetEvents">
                            Upcoming Events
                          </label>
                        </div>
                        
                        <div class="form-check">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="widgetComplaints"
                            v-model="preferences.dashboardWidgets.complaints"
                            @change="updatePreferences"
                          >
                          <label class="form-check-label" for="widgetComplaints">
                            Recent Complaints
                          </label>
                        </div>
                        
                        <div class="form-check">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="widgetDonations"
                            v-model="preferences.dashboardWidgets.donations"
                            @change="updatePreferences"
                          >
                          <label class="form-check-label" for="widgetDonations">
                            Donation Summary
                          </label>
                        </div>
                        
                        <div class="form-check">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="widgetCertificates"
                            v-model="preferences.dashboardWidgets.certificates"
                            @change="updatePreferences"
                          >
                          <label class="form-check-label" for="widgetCertificates">
                            Recent Certificates
                          </label>
                        </div>
                        
                        <div class="form-check">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="widgetActivity"
                            v-model="preferences.dashboardWidgets.activity"
                            @change="updatePreferences"
                          >
                          <label class="form-check-label" for="widgetActivity">
                            Recent Activity
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Privacy Controls -->
            <div v-if="activeSection === 'privacy'" class="settings-section">
              <div class="section-header">
                <h3>Privacy Controls</h3>
                <p>Control your privacy settings and data visibility</p>
              </div>

              <div class="settings-cards">
                <!-- Profile Visibility -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-eye me-2"></i>Profile Visibility</h5>
                  </div>
                  <div class="card-body">
                    <div class="privacy-options">
                      <div class="privacy-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="profilePublic"
                            v-model="privacySettings.profilePublic"
                            @change="updatePrivacy"
                          >
                          <label class="form-check-label" for="profilePublic">
                            <strong>Public Profile</strong>
                            <small class="d-block text-muted">Allow others to view your basic profile information</small>
                          </label>
                        </div>
                      </div>

                      <div class="privacy-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="showEmail"
                            v-model="privacySettings.showEmail"
                            @change="updatePrivacy"
                          >
                          <label class="form-check-label" for="showEmail">
                            <strong>Show Email Address</strong>
                            <small class="d-block text-muted">Display your email address on your public profile</small>
                          </label>
                        </div>
                      </div>

                      <div class="privacy-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="showPhone"
                            v-model="privacySettings.showPhone"
                            @change="updatePrivacy"
                          >
                          <label class="form-check-label" for="showPhone">
                            <strong>Show Phone Number</strong>
                            <small class="d-block text-muted">Display your phone number on your public profile</small>
                          </label>
                        </div>
                      </div>

                      <div class="privacy-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="showActivity"
                            v-model="privacySettings.showActivity"
                            @change="updatePrivacy"
                          >
                          <label class="form-check-label" for="showActivity">
                            <strong>Show Activity Status</strong>
                            <small class="d-block text-muted">Show when you were last active</small>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Data Sharing -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-share-alt me-2"></i>Data Sharing</h5>
                  </div>
                  <div class="card-body">
                    <div class="privacy-options">
                      <div class="privacy-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="analyticsData"
                            v-model="privacySettings.analyticsData"
                            @change="updatePrivacy"
                          >
                          <label class="form-check-label" for="analyticsData">
                            <strong>Analytics Data</strong>
                            <small class="d-block text-muted">Help improve our services by sharing anonymous usage data</small>
                          </label>
                        </div>
                      </div>

                      <div class="privacy-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="marketingData"
                            v-model="privacySettings.marketingData"
                            @change="updatePrivacy"
                          >
                          <label class="form-check-label" for="marketingData">
                            <strong>Marketing Communications</strong>
                            <small class="d-block text-muted">Receive personalized content and offers</small>
                          </label>
                        </div>
                      </div>

                      <div class="privacy-item">
                        <div class="form-check form-switch">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="thirdPartyData"
                            v-model="privacySettings.thirdPartyData"
                            @change="updatePrivacy"
                          >
                          <label class="form-check-label" for="thirdPartyData">
                            <strong>Third-Party Integrations</strong>
                            <small class="d-block text-muted">Allow data sharing with trusted third-party services</small>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Data Management -->
            <div v-if="activeSection === 'data'" class="settings-section">
              <div class="section-header">
                <h3>Data Management</h3>
                <p>Manage your personal data and account information</p>
              </div>

              <div class="settings-cards">
                <!-- Data Export -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-download me-2"></i>Data Export</h5>
                  </div>
                  <div class="card-body">
                    <p>Download a copy of your personal data stored in our system.</p>
                    
                    <div class="export-options">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportProfile" v-model="exportOptions.profile">
                        <label class="form-check-label" for="exportProfile">Profile Information</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportEvents" v-model="exportOptions.events">
                        <label class="form-check-label" for="exportEvents">Event History</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportComplaints" v-model="exportOptions.complaints">
                        <label class="form-check-label" for="exportComplaints">Complaint History</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportDonations" v-model="exportOptions.donations">
                        <label class="form-check-label" for="exportDonations">Donation History</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportCertificates" v-model="exportOptions.certificates">
                        <label class="form-check-label" for="exportCertificates">Certificates</label>
                      </div>
                    </div>

                    <div class="mt-3">
                      <button 
                        class="btn btn-primary"
                        @click="exportData"
                        :disabled="exportingData || !hasExportOptions"
                      >
                        <i class="fas me-2" :class="exportingData ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                        {{ exportingData ? 'Preparing Export...' : 'Export Data' }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Data Deletion -->
                <div class="settings-card border-danger">
                  <div class="card-header bg-danger text-white">
                    <h5><i class="fas fa-trash me-2"></i>Data Deletion</h5>
                  </div>
                  <div class="card-body">
                    <div class="alert alert-warning">
                      <i class="fas fa-exclamation-triangle me-2"></i>
                      <strong>Warning:</strong> These actions are irreversible. Please proceed with caution.
                    </div>

                    <div class="deletion-options">
                      <div class="deletion-item">
                        <h6>Clear Activity History</h6>
                        <p class="text-muted">Remove your activity logs and browsing history</p>
                        <button class="btn btn-outline-warning btn-sm" @click="clearActivityHistory">
                          <i class="fas fa-broom me-2"></i>
                          Clear History
                        </button>
                      </div>

                      <div class="deletion-item">
                        <h6>Delete Specific Data</h6>
                        <p class="text-muted">Permanently delete specific types of data</p>
                        <div class="btn-group" role="group">
                          <button class="btn btn-outline-danger btn-sm" @click="deleteEventHistory">
                            Delete Events
                          </button>
                          <button class="btn btn-outline-danger btn-sm" @click="deleteComplaintHistory">
                            Delete Complaints
                          </button>
                          <button class="btn btn-outline-danger btn-sm" @click="deleteDonationHistory">
                            Delete Donations
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Account Management -->
            <div v-if="activeSection === 'account'" class="settings-section">
              <div class="section-header">
                <h3>Account Management</h3>
                <p>Manage your account status and membership</p>
              </div>

              <div class="settings-cards">
                <!-- Account Status -->
                <div class="settings-card">
                  <div class="card-header">
                    <h5><i class="fas fa-user-check me-2"></i>Account Status</h5>
                  </div>
                  <div class="card-body">
                    <div class="account-info">
                      <div class="info-item">
                        <strong>Account Type:</strong>
                        <span class="badge bg-primary ms-2">{{ accountInfo.membershipType }}</span>
                      </div>
                      <div class="info-item">
                        <strong>Member Since:</strong>
                        <span>{{ formatDate(accountInfo.joinDate) }}</span>
                      </div>
                      <div class="info-item">
                        <strong>Account Status:</strong>
                        <span class="badge" :class="accountInfo.status === 'active' ? 'bg-success' : 'bg-warning'">
                          {{ accountInfo.status }}
                        </span>
                      </div>
                      <div class="info-item">
                        <strong>Email Verified:</strong>
                        <span class="badge" :class="accountInfo.emailVerified ? 'bg-success' : 'bg-warning'">
                          {{ accountInfo.emailVerified ? 'Verified' : 'Pending' }}
                        </span>
                      </div>
                    </div>

                    <div v-if="!accountInfo.emailVerified" class="mt-3">
                      <button class="btn btn-outline-primary" @click="resendVerificationEmail">
                        <i class="fas fa-envelope me-2"></i>
                        Resend Verification Email
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Account Actions -->
                <div class="settings-card border-danger">
                  <div class="card-header bg-danger text-white">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h5>
                  </div>
                  <div class="card-body">
                    <div class="danger-actions">
                      <div class="danger-item">
                        <h6>Deactivate Account</h6>
                        <p class="text-muted">Temporarily deactivate your account. You can reactivate it later.</p>
                        <button class="btn btn-outline-warning" @click="deactivateAccount">
                          <i class="fas fa-pause me-2"></i>
                          Deactivate Account
                        </button>
                      </div>

                      <div class="danger-item">
                        <h6>Delete Account</h6>
                        <p class="text-muted">Permanently delete your account and all associated data. This action cannot be undone.</p>
                        <button class="btn btn-danger" @click="deleteAccount">
                          <i class="fas fa-trash me-2"></i>
                          Delete Account
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div 
        class="toast" 
        :class="{ show: showSuccessToast }"
        role="alert"
      >
        <div class="toast-header">
          <i class="fas fa-check-circle text-success me-2"></i>
          <strong class="me-auto">Success</strong>
          <button type="button" class="btn-close" @click="showSuccessToast = false"></button>
        </div>
        <div class="toast-body">
          {{ successMessage }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/utils/api'

export default {
  name: 'MemberSettings',
  setup() {
    const authStore = useAuthStore()

    // Reactive data
    const loading = ref(false)
    const updating = ref(false)
    const changingPassword = ref(false)
    const exporting = ref(false)
    const exportingData = ref(false)
    const activeSection = ref('profile')
    const showSuccessToast = ref(false)
    const successMessage = ref('')
    const showCurrentPassword = ref(false)
    const showNewPassword = ref(false)
    const pushNotificationsEnabled = ref(false)

    // Profile settings
    const profileSettings = reactive({
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      bio: '',
      dateOfBirth: '',
      gender: '',
      avatar: ''
    })

    // Password form
    const passwordForm = reactive({
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    })

    // Security settings
    const securitySettings = reactive({
      twoFactorEnabled: false
    })

    // Active sessions
    const activeSessions = ref([])

    // Notification settings
    const notificationSettings = reactive({
      email: {
        events: true,
        complaints: true,
        donations: true,
        certificates: true,
        newsletter: true
      },
      sms: {
        urgent: true,
        events: false,
        complaints: true
      },
      push: {
        events: true,
        complaints: true,
        certificates: true
      }
    })

    // Preferences
    const preferences = reactive({
      theme: 'light',
      language: 'en',
      dateFormat: 'DD/MM/YYYY',
      timeFormat: '12',
      itemsPerPage: '25',
      dashboardWidgets: {
        events: true,
        complaints: true,
        donations: true,
        certificates: true,
        activity: true
      }
    })

    // Privacy settings
    const privacySettings = reactive({
      profilePublic: true,
      showEmail: false,
      showPhone: false,
      showActivity: true,
      analyticsData: true,
      marketingData: false,
      thirdPartyData: false
    })

    // Export options
    const exportOptions = reactive({
      profile: true,
      events: true,
      complaints: true,
      donations: true,
      certificates: true
    })

    // Account info
    const accountInfo = reactive({
      membershipType: 'Regular Member',
      joinDate: '2023-01-15',
      status: 'active',
      emailVerified: true
    })

    // Computed properties
    const passwordStrength = computed(() => {
      const password = passwordForm.newPassword
      if (!password) return { width: '0%', class: '', text: '' }

      let score = 0
      if (password.length >= 8) score++
      if (/[a-z]/.test(password)) score++
      if (/[A-Z]/.test(password)) score++
      if (/[0-9]/.test(password)) score++
      if (/[^A-Za-z0-9]/.test(password)) score++

      const levels = [
        { width: '20%', class: 'weak', text: 'Very Weak' },
        { width: '40%', class: 'weak', text: 'Weak' },
        { width: '60%', class: 'medium', text: 'Medium' },
        { width: '80%', class: 'strong', text: 'Strong' },
        { width: '100%', class: 'very-strong', text: 'Very Strong' }
      ]

      return levels[score - 1] || levels[0]
    })

    const isPasswordValid = computed(() => {
      return passwordForm.newPassword && 
             passwordForm.newPassword === passwordForm.confirmPassword &&
             passwordForm.newPassword.length >= 8
    })

    const hasNotificationChanges = computed(() => {
      // This would compare with original settings
      return false
    })

    const hasExportOptions = computed(() => {
      return Object.values(exportOptions).some(option => option)
    })

    // Methods
    const switchSection = (section) => {
      activeSection.value = section
    }

    const loadSettings = async () => {
      loading.value = true
      
      try {
        const response = await api.get('member/settings')
        
        // Update reactive objects
        Object.assign(profileSettings, response.data.profile || {})
        Object.assign(notificationSettings, response.data.notifications || notificationSettings)
        Object.assign(preferences, response.data.preferences || preferences)
        Object.assign(privacySettings, response.data.privacy || privacySettings)
        Object.assign(securitySettings, response.data.security || securitySettings)
        Object.assign(accountInfo, response.data.account || accountInfo)
        
        activeSessions.value = response.data.sessions || []
      } catch (err) {
        console.error('Error loading settings:', err)
      } finally {
        loading.value = false
      }
    }

    const updateProfile = async () => {
      updating.value = true
      
      try {
        const response = await api.put('member/profile', profileSettings)
        
        showSuccess('Profile updated successfully!')
      } catch (err) {
        console.error('Error updating profile:', err)
        alert('Failed to update profile')
      } finally {
        updating.value = false
      }
    }

    const handleAvatarUpload = async (event) => {
      const file = event.target.files[0]
      if (!file) return

      if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB')
        return
      }

      const formData = new FormData()
      formData.append('avatar', file)

      try {
        const response = await api.post('member/avatar', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        
        profileSettings.avatar = response.data.avatar_url
        showSuccess('Profile picture updated successfully!')
      } catch (err) {
        console.error('Error uploading avatar:', err)
        alert('Failed to upload profile picture')
      }
    }

    const removeAvatar = async () => {
      if (!confirm('Are you sure you want to remove your profile picture?')) return

      try {
        const response = await api.delete('member/avatar')
        
        profileSettings.avatar = ''
        showSuccess('Profile picture removed successfully!')
      } catch (err) {
        console.error('Error removing avatar:', err)
        alert('Failed to remove profile picture')
      }
    }

    const changePassword = async () => {
      if (!isPasswordValid.value) {
        alert('Please check your password requirements')
        return
      }

      changingPassword.value = true
      
      try {
        const response = await api.put('member/password', {
          current_password: passwordForm.currentPassword,
          new_password: passwordForm.newPassword
        })
        
        // Clear form
        Object.assign(passwordForm, {
          currentPassword: '',
          newPassword: '',
          confirmPassword: ''
        })
        showSuccess('Password changed successfully!')
      } catch (err) {
        console.error('Error changing password:', err)
        alert(err.response?.data?.message || 'Failed to change password')
      } finally {
        changingPassword.value = false
      }
    }

    const enableTwoFactor = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/2fa/enable', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          // Show QR code modal or setup instructions
          securitySettings.twoFactorEnabled = true
          showSuccess('Two-factor authentication enabled!')
        } else {
          throw new Error('Failed to enable 2FA')
        }
      } catch (err) {
        console.error('Error enabling 2FA:', err)
        alert('Failed to enable two-factor authentication')
      }
    }

    const disableTwoFactor = async () => {
      if (!confirm('Are you sure you want to disable two-factor authentication?')) return

      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/2fa/disable', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          securitySettings.twoFactorEnabled = false
          showSuccess('Two-factor authentication disabled!')
        } else {
          throw new Error('Failed to disable 2FA')
        }
      } catch (err) {
        console.error('Error disabling 2FA:', err)
        alert('Failed to disable two-factor authentication')
      }
    }

    const generateBackupCodes = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/2fa/backup-codes', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          // Show backup codes modal
          showSuccess('New backup codes generated!')
        } else {
          throw new Error('Failed to generate backup codes')
        }
      } catch (err) {
        console.error('Error generating backup codes:', err)
        alert('Failed to generate backup codes')
      }
    }

    const terminateSession = async (sessionId) => {
      if (!confirm('Are you sure you want to end this session?')) return

      try {
        const response = await fetch(`/backend/api.php/member/sessions/${sessionId}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          activeSessions.value = activeSessions.value.filter(s => s.id !== sessionId)
          showSuccess('Session terminated successfully!')
        } else {
          throw new Error('Failed to terminate session')
        }
      } catch (err) {
        console.error('Error terminating session:', err)
        alert('Failed to terminate session')
      }
    }

    const terminateAllSessions = async () => {
      if (!confirm('Are you sure you want to end all other sessions? You will remain logged in on this device.')) return

      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/sessions/terminate-all', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          activeSessions.value = activeSessions.value.filter(s => s.is_current)
          showSuccess('All other sessions terminated successfully!')
        } else {
          throw new Error('Failed to terminate sessions')
        }
      } catch (err) {
        console.error('Error terminating sessions:', err)
        alert('Failed to terminate sessions')
      }
    }

    const updateNotifications = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/notifications', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(notificationSettings)
        })

        if (response.ok) {
          showSuccess('Notification settings updated!')
        } else {
          throw new Error('Failed to update notifications')
        }
      } catch (err) {
        console.error('Error updating notifications:', err)
        alert('Failed to update notification settings')
      }
    }

    const enablePushNotifications = async () => {
      if ('Notification' in window) {
        const permission = await Notification.requestPermission()
        if (permission === 'granted') {
          pushNotificationsEnabled.value = true
          showSuccess('Push notifications enabled!')
        } else {
          alert('Push notifications permission denied')
        }
      } else {
        alert('Push notifications are not supported in this browser')
      }
    }

    const updatePreferences = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/preferences', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(preferences)
        })

        if (response.ok) {
          showSuccess('Preferences updated!')
        } else {
          throw new Error('Failed to update preferences')
        }
      } catch (err) {
        console.error('Error updating preferences:', err)
        alert('Failed to update preferences')
      }
    }

    const updatePrivacy = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/privacy', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(privacySettings)
        })

        if (response.ok) {
          showSuccess('Privacy settings updated!')
        } else {
          throw new Error('Failed to update privacy settings')
        }
      } catch (err) {
        console.error('Error updating privacy:', err)
        alert('Failed to update privacy settings')
      }
    }

    const exportData = async () => {
      exportingData.value = true
      
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/export', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(exportOptions)
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `bhrc-data-export-${new Date().toISOString().split('T')[0]}.zip`
          document.body.appendChild(a)
          a.click()
          document.body.removeChild(a)
          window.URL.revokeObjectURL(url)
          
          showSuccess('Data export completed!')
        } else {
          throw new Error('Failed to export data')
        }
      } catch (err) {
        console.error('Error exporting data:', err)
        alert('Failed to export data')
      } finally {
        exportingData.value = false
      }
    }

    const exportSettings = async () => {
      exporting.value = true
      
      try {
        const settingsData = {
          profile: profileSettings,
          notifications: notificationSettings,
          preferences: preferences,
          privacy: privacySettings
        }

        const blob = new Blob([JSON.stringify(settingsData, null, 2)], { type: 'application/json' })
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `bhrc-settings-${new Date().toISOString().split('T')[0]}.json`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
        
        showSuccess('Settings exported successfully!')
      } catch (err) {
        console.error('Error exporting settings:', err)
        alert('Failed to export settings')
      } finally {
        exporting.value = false
      }
    }

    const resetToDefaults = async () => {
      if (!confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) return

      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/settings/reset', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          // Reload settings
          await loadSettings()
          showSuccess('Settings reset to defaults!')
        } else {
          throw new Error('Failed to reset settings')
        }
      } catch (err) {
        console.error('Error resetting settings:', err)
        alert('Failed to reset settings')
      }
    }

    const clearActivityHistory = async () => {
      if (!confirm('Are you sure you want to clear your activity history? This action cannot be undone.')) return

      try {
        const response = await api.delete('member/activity/clear')
        showSuccess('Activity history cleared!')
      } catch (err) {
        console.error('Error clearing activity:', err)
        alert('Failed to clear activity history')
      }
    }

    const deleteEventHistory = async () => {
      if (!confirm('Are you sure you want to delete your event history? This action cannot be undone.')) return

      try {
        const response = await api.delete('member/events/delete-history')
        showSuccess('Event history deleted!')
      } catch (err) {
        console.error('Error deleting events:', err)
        alert('Failed to delete event history')
      }
    }

    const deleteComplaintHistory = async () => {
      if (!confirm('Are you sure you want to delete your complaint history? This action cannot be undone.')) return

      try {
        const response = await api.delete('member/complaints/delete-history')
        showSuccess('Complaint history deleted!')
      } catch (err) {
        console.error('Error deleting complaints:', err)
        alert('Failed to delete complaint history')
      }
    }

    const deleteDonationHistory = async () => {
      if (!confirm('Are you sure you want to delete your donation history? This action cannot be undone.')) return

      try {
        const response = await api.delete('member/donations/delete-history')
        showSuccess('Donation history deleted!')
      } catch (err) {
        console.error('Error deleting donations:', err)
        alert('Failed to delete donation history')
      }
    }

    const resendVerificationEmail = async () => {
      try {
        const response = await api.post('member/verify-email/resend')
        showSuccess('Verification email sent!')
      } catch (err) {
        console.error('Error sending verification:', err)
        alert('Failed to send verification email')
      }
    }

    const deactivateAccount = async () => {
      if (!confirm('Are you sure you want to deactivate your account? You can reactivate it later by logging in.')) return

      try {
        const response = await api.post('member/account/deactivate')
        alert('Account deactivated successfully. You will be logged out.')
        authStore.logout()
        this.$router.push('/login')
      } catch (err) {
        console.error('Error deactivating account:', err)
        alert('Failed to deactivate account')
      }
    }

    const deleteAccount = async () => {
      const confirmation = prompt('Type "DELETE" to confirm account deletion:')
      if (confirmation !== 'DELETE') {
        alert('Account deletion cancelled')
        return
      }

      try {
        const response = await api.delete('member/account/delete')
        alert('Account deleted successfully. We\'re sorry to see you go.')
        authStore.logout()
        this.$router.push('/')
      } catch (err) {
        console.error('Error deleting account:', err)
        alert('Failed to delete account')
      }
    }

    // Utility methods
    const getDeviceIcon = (deviceType) => {
      const icons = {
        desktop: 'fa-desktop',
        mobile: 'fa-mobile-alt',
        tablet: 'fa-tablet-alt'
      }
      return icons[deviceType] || 'fa-desktop'
    }

    const formatTime = (timestamp) => {
      return new Date(timestamp).toLocaleString()
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const showSuccess = (message) => {
      successMessage.value = message
      showSuccessToast.value = true
      setTimeout(() => {
        showSuccessToast.value = false
      }, 5000)
    }

    // Lifecycle hooks
    onMounted(() => {
      loadSettings()
      
      // Check push notification permission
      if ('Notification' in window && Notification.permission === 'granted') {
        pushNotificationsEnabled.value = true
      }
    })

    return {
      // Reactive data
      loading,
      updating,
      changingPassword,
      exporting,
      exportingData,
      activeSection,
      showSuccessToast,
      successMessage,
      showCurrentPassword,
      showNewPassword,
      pushNotificationsEnabled,
      profileSettings,
      passwordForm,
      securitySettings,
      activeSessions,
      notificationSettings,
      preferences,
      privacySettings,
      exportOptions,
      accountInfo,
      
      // Computed
      passwordStrength,
      isPasswordValid,
      hasNotificationChanges,
      hasExportOptions,
      
      // Methods
      switchSection,
      loadSettings,
      updateProfile,
      handleAvatarUpload,
      removeAvatar,
      changePassword,
      enableTwoFactor,
      disableTwoFactor,
      generateBackupCodes,
      terminateSession,
      terminateAllSessions,
      updateNotifications,
      enablePushNotifications,
      updatePreferences,
      updatePrivacy,
      exportData,
      exportSettings,
      resetToDefaults,
      clearActivityHistory,
      deleteEventHistory,
      deleteComplaintHistory,
      deleteDonationHistory,
      resendVerificationEmail,
      deactivateAccount,
      deleteAccount,
      getDeviceIcon,
      formatTime,
      formatDate,
      showSuccess
    }
  }
}
</script>

<style scoped>
/* Settings Page Styles */
.settings-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem 0;
}

.settings-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Header Styles */
.settings-header {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.settings-header h1 {
  color: #2c3e50;
  margin-bottom: 0.5rem;
  font-weight: 700;
}

.settings-header p {
  color: #7f8c8d;
  margin: 0;
}

/* Navigation Styles */
.settings-nav {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.nav-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: center;
}

.nav-pills .nav-link {
  border-radius: 25px;
  padding: 0.75rem 1.5rem;
  color: #6c757d;
  background: #f8f9fa;
  border: none;
  transition: all 0.3s ease;
  font-weight: 500;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-pills .nav-link:hover {
  background: #e9ecef;
  color: #495057;
  transform: translateY(-2px);
}

.nav-pills .nav-link.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Content Styles */
.settings-content {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  min-height: 600px;
}

.section-title {
  color: #2c3e50;
  margin-bottom: 1.5rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-title i {
  color: #667eea;
}

/* Form Styles */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
  display: block;
}

.form-control {
  border: 2px solid #e9ecef;
  border-radius: 10px;
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  outline: none;
}

.form-select {
  border: 2px solid #e9ecef;
  border-radius: 10px;
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
}

.form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  outline: none;
}

/* Button Styles */
.btn {
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-outline-primary {
  border: 2px solid #667eea;
  color: #667eea;
  background: transparent;
}

.btn-outline-primary:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
}

.btn-success {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
}

.btn-warning {
  background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
  color: white;
}

.btn-warning:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(255, 193, 7, 0.4);
}

.btn-danger {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
}

.btn-danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
  transform: translateY(-2px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Avatar Upload Styles */
.avatar-upload {
  text-align: center;
  margin-bottom: 2rem;
}

.avatar-preview {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  margin: 0 auto 1rem;
  border: 4px solid #e9ecef;
  overflow: hidden;
  position: relative;
  cursor: pointer;
  transition: all 0.3s ease;
}

.avatar-preview:hover {
  border-color: #667eea;
  transform: scale(1.05);
}

.avatar-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
}

.avatar-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
  color: white;
}

.avatar-preview:hover .avatar-overlay {
  opacity: 1;
}

/* Password Strength Indicator */
.password-strength {
  margin-top: 0.5rem;
}

.strength-bar {
  height: 4px;
  background: #e9ecef;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.strength-fill {
  height: 100%;
  transition: all 0.3s ease;
  border-radius: 2px;
}

.strength-fill.weak {
  width: 25%;
  background: #dc3545;
}

.strength-fill.fair {
  width: 50%;
  background: #ffc107;
}

.strength-fill.good {
  width: 75%;
  background: #fd7e14;
}

.strength-fill.strong {
  width: 100%;
  background: #28a745;
}

.strength-text {
  font-size: 0.875rem;
  font-weight: 500;
}

.strength-text.weak {
  color: #dc3545;
}

.strength-text.fair {
  color: #ffc107;
}

.strength-text.good {
  color: #fd7e14;
}

.strength-text.strong {
  color: #28a745;
}

/* Security Settings */
.security-item {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  border-left: 4px solid #667eea;
}

.security-item h6 {
  color: #2c3e50;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.security-item p {
  color: #6c757d;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.security-status {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.security-status.enabled {
  background: #d4edda;
  color: #155724;
}

.security-status.disabled {
  background: #f8d7da;
  color: #721c24;
}

/* Active Sessions */
.session-item {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.session-item:hover {
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.session-item.current {
  border-color: #28a745;
  background: #d4edda;
}

.session-header {
  display: flex;
  align-items: center;
  justify-content: between;
  margin-bottom: 1rem;
}

.session-device {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.device-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.session-info h6 {
  margin: 0;
  color: #2c3e50;
  font-weight: 600;
}

.session-info p {
  margin: 0;
  color: #6c757d;
  font-size: 0.875rem;
}

.session-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.session-detail {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6c757d;
  font-size: 0.875rem;
}

.current-badge {
  background: #28a745;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

/* Notification Settings */
.notification-group {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 1rem;
}

.notification-group h6 {
  color: #2c3e50;
  margin-bottom: 1rem;
  font-weight: 600;
}

.notification-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e9ecef;
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-info h6 {
  margin: 0;
  color: #2c3e50;
  font-size: 0.9rem;
  font-weight: 600;
}

.notification-info p {
  margin: 0;
  color: #6c757d;
  font-size: 0.8rem;
}

/* Toggle Switch */
.form-check-input {
  width: 3rem;
  height: 1.5rem;
  border-radius: 1rem;
  background-color: #e9ecef;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.form-check-input:checked {
  background-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-check-input:focus {
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Export Options */
.export-options {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.export-options h6 {
  color: #2c3e50;
  margin-bottom: 1rem;
  font-weight: 600;
}

.export-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
}

.export-item input[type="checkbox"] {
  width: 1.2rem;
  height: 1.2rem;
  accent-color: #667eea;
}

.export-item label {
  color: #2c3e50;
  font-weight: 500;
  cursor: pointer;
  margin: 0;
}

/* Account Info */
.account-info {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 15px;
  padding: 2rem;
  margin-bottom: 2rem;
}

.account-info h5 {
  margin-bottom: 1rem;
  font-weight: 600;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  padding: 1rem;
  backdrop-filter: blur(10px);
}

.info-item h6 {
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  opacity: 0.9;
}

.info-item p {
  margin: 0;
  font-size: 0.9rem;
  opacity: 0.8;
}

/* Danger Zone */
.danger-zone {
  background: #fff5f5;
  border: 2px solid #fed7d7;
  border-radius: 15px;
  padding: 2rem;
  margin-top: 2rem;
}

.danger-zone h5 {
  color: #c53030;
  margin-bottom: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.danger-zone p {
  color: #742a2a;
  margin-bottom: 1.5rem;
}

.danger-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

/* Success Toast */
.success-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #28a745;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  z-index: 1050;
  animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Loading States */
.loading-spinner {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid #f3f3f3;
  border-top: 2px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 15px;
  z-index: 10;
}

.loading-overlay .loading-spinner {
  width: 2rem;
  height: 2rem;
  border-width: 3px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .settings-page {
    padding: 1rem 0;
  }

  .settings-container {
    padding: 0 0.5rem;
  }

  .settings-header,
  .settings-nav,
  .settings-content {
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .nav-pills {
    flex-direction: column;
    align-items: stretch;
  }

  .nav-pills .nav-link {
    justify-content: center;
    margin-bottom: 0.5rem;
  }

  .info-grid,
  .session-details {
    grid-template-columns: 1fr;
  }

  .session-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .danger-actions {
    flex-direction: column;
  }

  .success-toast {
    top: 1rem;
    right: 1rem;
    left: 1rem;
  }
}

@media (max-width: 576px) {
  .avatar-preview {
    width: 100px;
    height: 100px;
  }

  .btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }

  .section-title {
    font-size: 1.25rem;
  }
}

/* Print Styles */
@media print {
  .settings-page {
    background: white !important;
    padding: 0 !important;
  }

  .settings-nav,
  .btn,
  .success-toast {
    display: none !important;
  }

  .settings-content {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .settings-page {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  }

  .settings-header,
  .settings-nav,
  .settings-content {
    background: #34495e;
    color: #ecf0f1;
  }

  .section-title,
  .form-label {
    color: #ecf0f1;
  }

  .form-control,
  .form-select {
    background: #2c3e50;
    border-color: #4a5568;
    color: #ecf0f1;
  }

  .form-control:focus,
  .form-select:focus {
    background: #2c3e50;
    border-color: #667eea;
    color: #ecf0f1;
  }

  .security-item,
  .session-item,
  .notification-group,
  .export-options {
    background: #2c3e50;
    border-color: #4a5568;
  }

  .session-item:hover {
    background: #3a4a5c;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus Styles for Accessibility */
.btn:focus,
.form-control:focus,
.form-select:focus,
.nav-link:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .btn-primary {
    background: #000;
    border: 2px solid #fff;
  }

  .btn-outline-primary {
    border-color: #000;
    color: #000;
  }

  .form-control,
  .form-select {
    border-color: #000;
  }
}
</style>