let pendingForm = null
let lastFocused = null

const dialog = document.querySelector('[data-dialog-box]')

function openDialog(form) {
  pendingForm = form
  lastFocused = document.activeElement
  dialog.querySelector('[data-dialog-message]').textContent = form.dataset.dialogConfirm || 'Are you sure?'
  dialog.classList.remove('hidden')
  dialog.querySelector('[data-dialog-ok]').focus()
}

function closeDialog() {
  if (!dialog.classList.contains('hidden')) {
    dialog.classList.add('hidden')
    lastFocused?.focus()
  }
  pendingForm = null
}

// Intercept submit on any form carrying data-confirm
document.addEventListener('submit', (e) => {
  const form = e.target.closest('form[data-dialog-confirm]')
  if (!form) return

  // 'confirmed' flag set by the OK button -> let this submit through
  if (form.dataset.confirmed) {
    delete form.dataset.confirmed
    return
  }

  e.preventDefault()
  openDialog(form)
})

document.addEventListener('click', (e) => {
  if (e.target.closest('[data-dialog-ok]')) {
    if (!pendingForm) return
    pendingForm.dataset.confirmed = '1'
    pendingForm.requestSubmit() // fires submit again, the flag lets it pass
  } else if (e.target.closest('[data-dialog-cancel]') || e.target.closest('[data-dialog-backdrop]')) {
    closeDialog()
  }
})

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeDialog()
})
