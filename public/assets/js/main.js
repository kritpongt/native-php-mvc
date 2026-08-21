document.addEventListener('DOMContentLoaded', () => {
  // ## Layouts
  mobileMenu()
  userMenuBtn()
  // ## End
})

document.addEventListener('htmx:afterSettle', () => {})

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

function mobileMenu() {
  const openBtn = document.getElementById('open-mobile-menu')
  const closeBtn = document.getElementById('close-mobile-menu')
  const drawer = document.getElementById('mobile-menu-drawer')
  const backdrop = document.getElementById('mobile-menu-backdrop')

  function toggleMobileMenu() {
    if (drawer) {
      drawer.classList.toggle('hidden')
    }
  }

  if (openBtn) openBtn.addEventListener('click', toggleMobileMenu)
  if (closeBtn) closeBtn.addEventListener('click', toggleMobileMenu)
  if (backdrop) backdrop.addEventListener('click', toggleMobileMenu)

  window.addEventListener('resize', () => {
    if (window.innerWidth >= 768) {
      if (drawer && !drawer.classList.contains('hidden')) {
        drawer.classList.add('hidden')
      }
    }
  })
}
