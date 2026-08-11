document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-noti-close]')
  if (!btn) return
  btn.closest('[data-noti]')?.remove()
})

// Auto-dismiss - htmx:load fires on initial load AND after every swap
document.addEventListener('htmx:load', (e) => {
  const scope = e.detail?.elt ?? document
  scope.querySelectorAll('[data-autoclose]').forEach((el) => {
    if (el.dataset.notiScheduled) return
    el.dataset.notiScheduled = '1'
    const ms = parseInt(el.dataset.autoclose, 10)
    if (!(ms > 0)) return
    el.querySelector('.noti-timer')?.style.setProperty('animation-duration', `${ms}ms`)
    setTimeout(() => el.remove(), ms)
  })
})
