document.addEventListener('click', (e) => {
  // Add Item
  const btnAdd = e.target.closest('[data-action="quotation-add"]')
  if (btnAdd) {
    const container = document.querySelector('[data-quotation-items]')
    const template = document.querySelector('[data-quotation-template]')
    if (container && template) {
      const clone = template.content.cloneNode(true)
      container.appendChild(clone)
      updateItemSequence()
    }
    return
  }

  // Remove Item
  const btnRemove = e.target.closest('[data-action="quotation-remove"]')
  if (btnRemove) {
    const container = document.querySelector('[data-quotation-items]')
    if (container) {
      // Ensure we don't delete the last row
      if (container.querySelectorAll('[data-quotation-row]').length > 1) {
        btnRemove.closest('[data-quotation-row]').remove()
        updateItemSequence()
      }
    }
    return
  }
})

document.addEventListener('change', (e) => {
  if (e.target.matches('[name="is_new_customer"]')) {
    const isNew = e.target.checked
    const existingContainer = document.getElementById('existing_customer_container')
    const newContainer = document.getElementById('new_customer_container')

    if (!existingContainer || !newContainer) return

    const existingSelect = existingContainer.querySelector('select')
    const newNameInput = newContainer.querySelector('[name="new_customer_name"]')
    const wrapper = document.getElementById('new_customer_wrapper')

    if (isNew) {
      if (wrapper) {
        wrapper.classList.remove('grid-rows-[0fr]', 'opacity-0', 'mt-0')
        wrapper.classList.add('grid-rows-[1fr]', 'opacity-100', 'mt-4')
      }

      if (existingSelect) {
        existingSelect.required = false
        existingSelect.disabled = true
      }
      if (newNameInput) newNameInput.required = true
    } else {
      if (wrapper) {
        wrapper.classList.add('grid-rows-[0fr]', 'opacity-0', 'mt-0')
        wrapper.classList.remove('grid-rows-[1fr]', 'opacity-100', 'mt-4')
      }

      if (existingSelect) {
        existingSelect.required = true
        existingSelect.disabled = false
      }
      if (newNameInput) newNameInput.required = false
    }
  }
})

// Initialize SortableJS
document.addEventListener('htmx:load', (e) => {
  const scope = e.detail?.elt ?? document

  // We check if the data-quotation-items container exists in the newly loaded HTML
  const container = scope.querySelector ? scope.querySelector('[data-quotation-items]') : null
  const actualContainer = scope.hasAttribute && scope.hasAttribute('data-quotation-items') ? scope : container

  if (actualContainer && typeof Sortable !== 'undefined') {
    // Prevent double initialization
    if (actualContainer.dataset.sortableInitialized) return
    actualContainer.dataset.sortableInitialized = '1'

    new Sortable(actualContainer, {
      animation: 150,
      handle: '[data-quotation-drag]',
      ghostClass: 'opacity-50',
      onEnd: function () {
        updateItemSequence()
      }
    })
  }
})

function updateItemSequence() {
  const container = document.querySelector('[data-quotation-items]')
  if (!container) return

  const numbers = container.querySelectorAll('[data-quotation-item-seq]')

  numbers.forEach((el, index) => {
    el.textContent = `${index + 1}.`
  })
}
