document.addEventListener('DOMContentLoaded', () => {
  // ## Layouts
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
  // ## End
})
