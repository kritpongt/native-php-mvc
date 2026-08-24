let pendingElement = null
let pendingHtmxEvent = null
let lastFocused = null

const dialog = document.querySelector('[data-dialog-box]')

// Track previous values of selects in case we need to revert them on cancel
document.addEventListener('focusin', (e) => {
  if (e.target.tagName === 'SELECT') {
    // Only track if the select itself or its options might trigger a dialog
    const hasDialog =
      e.target.hasAttribute('data-dialog-confirm') || e.target.querySelector('option[data-dialog-confirm]') !== null
    if (hasDialog) {
      e.target.dataset.oldValue = e.target.value
    }
  }
})

function openDialog(element, configSource = element) {
  if (!dialog) return
  pendingElement = element
  lastFocused = document.activeElement

  // Set messages
  dialog.querySelector('[data-dialog-message]').textContent = configSource.dataset.dialogConfirm || 'Are you sure?'

  // Set OK button text
  const btnOk = dialog.querySelector('[data-dialog-ok]')
  btnOk.textContent = configSource.dataset.dialogOkText || btnOk.dataset.defaultText || 'Confirm'

  // Apply Theme (primary, warning, danger)
  const theme = configSource.dataset.dialogTheme || 'primary' // default to primary
  const iconContainer = dialog.querySelector('[data-dialog-icon-container]')

  const baseBtnClass = 'px-4 py-2 text-sm font-medium rounded-lg transition-colors'
  const baseIconClass = 'w-9 h-9 shrink-0 grid place-items-center rounded-full'

  if (theme === 'primary') {
    btnOk.className = `${baseBtnClass} bg-accent text-accent-fg hover:opacity-90`
    iconContainer.className = `${baseIconClass} bg-accent/10 text-accent`
  } else if (theme === 'warning') {
    btnOk.className = `${baseBtnClass} bg-warning-text text-white hover:opacity-90`
    iconContainer.className = `${baseIconClass} bg-warning-bg text-warning-text`
  } else {
    btnOk.className = `${baseBtnClass} bg-danger-text text-white hover:opacity-90`
    iconContainer.className = `${baseIconClass} bg-danger-bg text-danger-text`
  }

  dialog.classList.remove('hidden')
  btnOk.focus()
}

function closeDialog() {
  if (dialog && !dialog.classList.contains('hidden')) {
    dialog.classList.add('hidden')
    lastFocused?.focus()
  }
  pendingElement = null
  pendingHtmxEvent = null
}

// Intercept submit on any form carrying data-confirm (for standard forms)
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

// Intercept HTMX confirmation
document.addEventListener('htmx:confirm', (e) => {
  let trigger = e.target.closest('[data-dialog-confirm]')
  let configSource = trigger

  // If the trigger itself doesn't have the attribute, check if it's a SELECT with an option that has it
  if (!trigger && e.target.tagName === 'SELECT') {
    const selectedOption = e.target.options[e.target.selectedIndex]
    if (selectedOption && selectedOption.hasAttribute('data-dialog-confirm')) {
      trigger = e.target // The SELECT remains the HTMX trigger
      configSource = selectedOption // But the config comes from the OPTION
    }
  }

  if (!trigger) return

  e.preventDefault() // Stop HTMX from issuing the request immediately
  openDialog(trigger, configSource)
  pendingHtmxEvent = e
})

document.addEventListener('click', (e) => {
  if (e.target.closest('[data-dialog-ok]')) {
    if (pendingHtmxEvent) {
      pendingHtmxEvent.detail.issueRequest(true) // trigger htmx request
      // Update the old value so it doesn't revert incorrectly if focused again before reload
      if (pendingElement && pendingElement.tagName === 'SELECT') {
        pendingElement.dataset.oldValue = pendingElement.value
      }
      closeDialog()
    } else if (pendingElement && pendingElement.tagName === 'FORM') {
      pendingElement.dataset.confirmed = '1'
      pendingElement.requestSubmit() // fires submit again, the flag lets it pass
      closeDialog()
    }
  } else if (e.target.closest('[data-dialog-cancel]') || e.target.closest('[data-dialog-backdrop]')) {
    // If we are cancelling a SELECT change, revert it to its old value
    if (pendingHtmxEvent && pendingElement && pendingElement.tagName === 'SELECT') {
      if (pendingElement.dataset.oldValue !== undefined) {
        pendingElement.value = pendingElement.dataset.oldValue
      }
    }
    closeDialog()
  }
})

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    if (dialog && !dialog.classList.contains('hidden')) {
      if (pendingHtmxEvent && pendingElement && pendingElement.tagName === 'SELECT') {
        if (pendingElement.dataset.oldValue !== undefined) {
          pendingElement.value = pendingElement.dataset.oldValue
        }
      }
      closeDialog()
    }
  }
})
