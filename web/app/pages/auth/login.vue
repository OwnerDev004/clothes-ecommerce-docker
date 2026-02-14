<template>
  <div class="login-container">
    <!-- Background Design -->
    <div class="background-design">
      <div class="gradient-bg"></div>
      <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
      </div>
    </div>

    <!-- Login Content -->
    <div class="login-content">
      <!-- Left Panel - Branding -->
      <div class="brand-panel">
        <div class="brand-logo">
          <div class="logo-wrapper">
            <span class="logo-icon">🔐</span>
          </div>
          <h1 class="brand-name">SecurePortal</h1>
        </div>
        
        <div class="brand-quote">
          <h2>Welcome Back</h2>
          <p>Sign in to access your personalized workspace and continue your journey with us.</p>
        </div>

        <div class="features-list">
          <div class="feature-item">
            <div class="feature-icon">✓</div>
            <div class="feature-text">
              <h4>Enterprise Security</h4>
              <p>Military-grade encryption and protection</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">⚡</div>
            <div class="feature-text">
              <h4>Lightning Fast</h4>
              <p>Quick authentication and loading times</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">🔒</div>
            <div class="feature-text">
              <h4>Data Privacy</h4>
              <p>Your data is safe with us</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Panel - Login Form -->
      <div class="form-panel">
        <div class="form-container">
          <div class="form-header">
            <h2>Sign In to Your Account</h2>
            <p>Enter your credentials to continue</p>
          </div>

          <!-- Login Form -->
          <form class="login-form" @submit.prevent="submitLogin">
            <!-- Email Input -->
            <div class="form-group">
              <label class="form-label">
                <span>Username</span>
                <span class="required">*</span>
              </label>
              <div class="input-container">
                <div class="input-prefix">
                  <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                </div>
                <input 
                  type="text" 
                  class="form-input"
                  placeholder="your_username"
                  v-model="userName"
                >
              </div>
              <div class="input-hint">Enter your registered username</div>
            </div>

            <!-- Password Input -->
            <div class="form-group">
              <label class="form-label">
                <span>Password</span>
                <span class="required">*</span>
              </label>
              <div class="input-container">
                <div class="input-prefix">
                  <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                  </svg>
                </div>
                <input 
                  type="password" 
                  class="form-input"
                  placeholder="Enter your password"
                  v-model="password"
                >
                <button type="button" class="password-toggle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
              <div class="password-strength">
                <div class="strength-meter">
                  <div class="strength-bar"></div>
                </div>
                <span class="strength-text">Password strength: Weak</span>
              </div>
            </div>

            <!-- Form Options -->
            <div class="form-options">
              <label class="checkbox-container">
                <input type="checkbox" class="checkbox-input">
                <span class="checkbox-checkmark"></span>
                <span class="checkbox-label">Remember me for 30 days</span>
              </label>
              <NuxtLink to="/auth/forgot-password" class="forgot-link">Forgot password?</NuxtLink>
            </div>

            <div v-if="errorMessage" class="form-error">{{ errorMessage }}</div>

            <!-- Submit Button -->
            <button type="submit" class="submit-button" :disabled="loading">
              <span>Sign In</span>
              <svg class="button-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
              </svg>
            </button>

            <!-- Divider -->
            <div class="divider">
              <span>or continue with</span>
            </div>

            <!-- Social Login -->
            <div class="social-login">
              <button type="button" class="social-button google" @click="onGoogleLogin">
                <svg class="social-icon" viewBox="0 0 24 24">
                  <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                  <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                  <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"></path>
                  <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"></path>
                </svg>
                <span>Google</span>
              </button>
              
              <button type="button" class="social-button facebook" @click="onFacebookLogin">
                <svg class="social-icon" viewBox="0 0 24 24">
                  <path d="M22 12.06C22 6.5 17.52 2 11.94 2 6.37 2 1.88 6.5 1.88 12.06c0 5.02 3.66 9.19 8.44 9.94v-7.03H7.9v-2.91h2.42V9.41c0-2.4 1.43-3.73 3.62-3.73 1.05 0 2.15.19 2.15.19v2.37h-1.21c-1.2 0-1.57.75-1.57 1.52v1.83h2.67l-.43 2.91h-2.24V22c4.78-.75 8.44-4.92 8.44-9.94z" fill="#1877F2"></path>
                </svg>
                <span>Facebook</span>
              </button>
               <button type="button" class="social-button google" @click="onGithubLogin">
                <svg class="social-icon" viewBox="0 0 24 24">
                  <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                  <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                  <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"></path>
                  <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"></path>
                </svg>
                <span>Github</span>
              </button>
            </div>

            <!-- Sign Up Link -->
            <div class="signup-link">
              <span>Don't have an account?</span>
              <NuxtLink to="/auth/signup" class="signup-button">Create account</NuxtLink>
            </div>
          </form>

          <!-- Footer -->
          <div class="form-footer">
            <div class="language-selector">
              <select class="language-select">
                <option value="en">🌐 English</option>
                <option value="zh">🇨🇳 中文</option>
                <option value="es">🇪🇸 Español</option>
                <option value="fr">🇫🇷 Français</option>
              </select>
            </div>
            <div class="footer-links">
              <a href="#">Terms of Service</a>
              <a href="#">Privacy Policy</a>
              <a href="#">Help Center</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/authStore'

declare global {
  interface Window {
    google?: any
    FB?: any
    fbAsyncInit?: () => void
  }
}

definePageMeta({
  middleware: ['guest']
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const apiOrigin = apiBase.replace(/\/api\/v1\/?$/, '')
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const userName = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

const submitLogin = async () => {
  errorMessage.value = ''
  if (!userName.value || !password.value) {
    errorMessage.value = 'Username and password are required'
    return
  }
  loading.value = true
  try {
    await $fetch(`${apiBase}/auth/login`, {
      method: 'POST',
      credentials: 'include',
      body: {
        user_name: userName.value,
        password: password.value
      }
    })
    authStore.setAuthenticated(true)
    await router.replace('/')
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Login failed. Please try again.'
    authStore.resetAuth()
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (import.meta.client) {
    const errorParam = route.query.error
    const error = Array.isArray(errorParam) ? errorParam[0] : errorParam
    if (error) {
      errorMessage.value = 'OAuth login failed. Please try again.'
    }

    const successParam = route.query.success
    const success = Array.isArray(successParam) ? successParam[0] : successParam
    if (success) {
      authStore.setAuthenticated(true)
      router.replace('/')
      return
    }
  }
})

const onGoogleLogin = async () => {
  errorMessage.value = ''
  try {
    const frontendRedirect = `${window.location.origin}/auth/login`
    const redirectUrl = `${apiOrigin}/auth/google/redirect?redirect=${encodeURIComponent(frontendRedirect)}`
    window.location.href = redirectUrl
  } catch (e: any) {
    errorMessage.value = e?.message || 'Failed to start Google login'
  }
}

const onFacebookLogin = async () => {
  errorMessage.value = ''
  try {
    const frontendRedirect = `${window.location.origin}/auth/login`
    const redirectUrl = `${apiOrigin}/auth/facebook/redirect?redirect=${encodeURIComponent(frontendRedirect)}`
    window.location.href = redirectUrl
  } catch (e: any) {
    errorMessage.value = e?.message || 'Failed to start Facebook login'
  }
}

const onGithubLogin = async () => {
  errorMessage.value = ''
  try {
    const frontendRedirect = `${window.location.origin}/auth/login`
    const redirectUrl = `${apiOrigin}/auth/github/redirect?redirect=${encodeURIComponent(frontendRedirect)}`
    window.location.href = redirectUrl
  } catch (e: any) {
    errorMessage.value = e?.message || 'Failed to start GitHub login'
  }
}
</script>

<style scoped>
/* Reset and Base Styles */
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  position: relative;
  overflow: hidden;
}

/* Background Design */
.background-design {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
}

.gradient-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
  opacity: 0.05;
}

.floating-shapes {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.shape {
  position: absolute;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  animation: float 20s infinite ease-in-out;
}

.shape-1 {
  width: 400px;
  height: 400px;
  top: -200px;
  right: -100px;
  animation-delay: 0s;
}

.shape-2 {
  width: 300px;
  height: 300px;
  bottom: -150px;
  left: -50px;
  animation-delay: 5s;
}

.shape-3 {
  width: 200px;
  height: 200px;
  top: 50%;
  right: 10%;
  animation-delay: 10s;
}

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  33% { transform: translateY(-20px) rotate(120deg); }
  66% { transform: translateY(20px) rotate(240deg); }
}

/* Login Content Layout */
.login-content {
  display: flex;
  max-width: 1200px;
  width: 100%;
  background: white;
  border-radius: 32px;
  box-shadow: 0 20px 80px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  z-index: 2;
  margin: 24px;
}

/* Brand Panel */
.brand-panel {
  flex: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 64px 48px;
  display: flex;
  flex-direction: column;
}

.brand-logo {
  margin-bottom: 48px;
}

.logo-wrapper {
  width: 64px;
  height: 64px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.logo-icon {
  font-size: 32px;
}

.brand-name {
  font-size: 28px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.5px;
}

.brand-quote {
  margin-bottom: 48px;
}

.brand-quote h2 {
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 16px 0;
  line-height: 1.2;
}

.brand-quote p {
  font-size: 16px;
  line-height: 1.6;
  opacity: 0.9;
  margin: 0;
}

.features-list {
  margin-top: auto;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
}

.feature-icon {
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.feature-text h4 {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 4px 0;
}

.feature-text p {
  font-size: 14px;
  opacity: 0.8;
  margin: 0;
  line-height: 1.4;
}

/* Form Panel */
.form-panel {
  flex: 1.2;
  padding: 64px 48px;
  background: white;
}

.form-container {
  max-width: 400px;
  margin: 0 auto;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.form-header {
  margin-bottom: 40px;
  text-align: center;
}

.form-header h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.form-header p {
  font-size: 16px;
  color: #666;
  margin: 0;
}

/* Form Styles */
.login-form {
  flex: 1;
}

.form-group {
  margin-bottom: 28px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.required {
  color: #ef4444;
}

.input-container {
  position: relative;
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.input-container:focus-within {
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-prefix {
  padding: 0 16px;
  display: flex;
  align-items: center;
}

.input-icon {
  width: 20px;
  height: 20px;
  color: #64748b;
  stroke-width: 2;
}

.form-input {
  flex: 1;
  padding: 16px 16px 16px 0;
  border: none;
  background: transparent;
  font-size: 16px;
  color: #1a1a1a;
  outline: none;
  min-width: 0;
}

.form-input::placeholder {
  color: #94a3b8;
}

.password-toggle {
  padding: 0 16px;
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.password-toggle svg {
  width: 20px;
  height: 20px;
  color: #64748b;
  stroke-width: 2;
}

.input-hint {
  font-size: 12px;
  color: #64748b;
  margin-top: 6px;
  margin-left: 4px;
}

.password-strength {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 8px;
}

.strength-meter {
  flex: 1;
  height: 4px;
  background: #e2e8f0;
  border-radius: 2px;
  overflow: hidden;
}

.strength-bar {
  width: 40%;
  height: 100%;
  background: #f59e0b;
  border-radius: 2px;
  transition: all 0.3s ease;
}

.strength-text {
  font-size: 12px;
  color: #64748b;
  white-space: nowrap;
}

/* Form Options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  user-select: none;
}

.checkbox-input {
  display: none;
}

.checkbox-checkmark {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e1;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  position: relative;
}

.checkbox-checkmark::after {
  content: '✓';
  position: absolute;
  color: white;
  font-size: 12px;
  opacity: 0;
  transform: scale(0);
  transition: all 0.3s ease;
}

.checkbox-input:checked + .checkbox-checkmark {
  background: #667eea;
  border-color: #667eea;
}

.checkbox-input:checked + .checkbox-checkmark::after {
  opacity: 1;
  transform: scale(1);
}

.checkbox-label {
  font-size: 14px;
  color: #475569;
}

.forgot-link {
  font-size: 14px;
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
}

.forgot-link:hover {
  color: #5a67d8;
  text-decoration: underline;
}

/* Submit Button */
.submit-button {
  width: 100%;
  padding: 18px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-bottom: 32px;
}

.submit-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.submit-button:active {
  transform: translateY(0);
}

.button-icon {
  width: 20px;
  height: 20px;
  stroke-width: 3;
}

/* Divider */
.divider {
  display: flex;
  align-items: center;
  margin: 32px 0;
  color: #94a3b8;
  font-size: 14px;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.divider span {
  padding: 0 16px;
  background: white;
}

/* Social Login */
.social-login {
  display: flex;
  gap: 16px;
  margin-bottom: 32px;
}

.social-button {
  flex: 1;
  padding: 14px 20px;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  font-size: 14px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.3s ease;
}

.social-button:hover {
  transform: translateY(-2px);
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.social-icon {
  width: 20px;
  height: 20px;
}

.google:hover {
  border-color: #db4437;
  color: #db4437;
}

.github:hover {
  border-color: #333;
  color: #333;
}
.facebook:hover {
  border-color: #1877f2;
  color: #1877f2;
}

.form-error {
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fecaca;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 13px;
  margin: 10px 0 18px;
}

/* Sign Up Link */
.signup-link {
  text-align: center;
  font-size: 14px;
  color: #64748b;
  margin-top: 24px;
}

.signup-button {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  margin-left: 8px;
  transition: color 0.3s ease;
}

.signup-button:hover {
  color: #5a67d8;
  text-decoration: underline;
}

/* Form Footer */
.form-footer {
  margin-top: auto;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.language-select {
  padding: 8px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #475569;
  font-size: 14px;
  cursor: pointer;
  outline: none;
  transition: border-color 0.3s ease;
}

.language-select:focus {
  border-color: #667eea;
}

.footer-links {
  display: flex;
  gap: 24px;
}

.footer-links a {
  font-size: 12px;
  color: #64748b;
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer-links a:hover {
  color: #667eea;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .login-content {
    flex-direction: column;
    max-width: 600px;
  }

  .brand-panel {
    padding: 48px 32px;
  }

  .form-panel {
    padding: 48px 32px;
  }
}

@media (max-width: 640px) {
  .login-content {
    margin: 16px;
  }

  .brand-panel,
  .form-panel {
    padding: 32px 24px;
  }

  .social-login {
    flex-direction: column;
  }

  .form-footer {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }

  .footer-links {
    flex-wrap: wrap;
    gap: 16px;
  }
}

@media (max-width: 480px) {
  .login-content {
    margin: 8px;
    border-radius: 24px;
  }

  .brand-panel,
  .form-panel {
    padding: 24px 20px;
  }

  .brand-quote h2 {
    font-size: 24px;
  }

  .form-header h2 {
    font-size: 24px;
  }
}

/* Animation for interactive elements */
@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
  100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
}

.submit-button:focus {
  animation: pulse 1.5s infinite;
}
</style>
