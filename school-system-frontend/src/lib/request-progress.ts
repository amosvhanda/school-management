import NProgress from 'nprogress'

let activeRequests = 0

export function configureRequestProgress() {
  NProgress.configure({
    showSpinner: false,
    trickleSpeed: 120,
    minimum: 0.08,
  })
}

export function startRequestProgress() {
  if (activeRequests === 0) {
    NProgress.start()
  }
  activeRequests += 1
}

export function finishRequestProgress() {
  activeRequests = Math.max(0, activeRequests - 1)
  if (activeRequests === 0) {
    NProgress.done()
  }
}
