document.addEventListener('DOMContentLoaded', () => {
  // ## Layouts
  userMenuBtn()
  // ## End
})

document.addEventListener('htmx:afterSettle', () => {
  // ## Layouts
  // updateActiveSidemenu()
  // ## End
})

function userMenuBtn() {
  const btn = document.getElementById('userMenuBtn')
  const dropdown = document.getElementById('userDropdown')
  const container = document.getElementById('userMenuContainer')

  btn?.addEventListener('click', (e) => {
    e.stopPropagation()
    dropdown.classList.toggle('hidden')
  })

  document.addEventListener('click', (e) => {
    if (!container?.contains(e.target)) {
      dropdown?.classList.add('hidden')
    }
  })
}

// function updateActiveSidemenu() {
//   const currentPath = window.location.pathname
//   const sidemenuNav = document.getElementById('sidemenu-nav')
//   if (!sidemenuNav) return

//   const activeClasses = ['bg-accent-bg', 'text-accent-text', 'font-medium']
//   const inactiveClasses = ['text-fg-secondary', 'hover:bg-surface-1']

//   const currentActive = sidemenuNav.querySelector('.bg-accent-bg')
//   if (currentActive) {
//     currentActive.classList.remove(...activeClasses)
//     currentActive.classList.add(...inactiveClasses)
//   }

//   const newActive = sidemenuNav.querySelector(`a[href="${currentPath}"]`)
//   if (newActive) {
//     newActive.classList.add(...activeClasses)
//     newActive.classList.remove(...inactiveClasses)
//   }
// }

// ## Components
// components/noti.html.twig
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
// ## End
