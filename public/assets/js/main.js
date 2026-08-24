document.addEventListener('DOMContentLoaded', () => {
  // ## Layouts
  mobileMenu()
  userMenuBtn()
})

document.addEventListener('htmx:load', (e) => {
  // ## Datepicker Global Initialization
  flatpickrInit(e)
})

// document.addEventListener('htmx:afterSettle', () => {})

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

function flatpickrInit(e) {
  const scope = e.detail?.elt ?? document
  const pickers = scope.querySelectorAll('.js-datepicker')

  if (pickers.length === 0 || typeof flatpickr === 'undefined') return

  const currentLang = document.documentElement.lang || 'en'

  pickers.forEach((el) => {
    if (el._flatpickr) return

    flatpickr(el, {
      locale: currentLang === 'th' ? 'th' : 'default',
      altInput: true,
      altFormat: 'j M Y',
      dateFormat: 'Y-m-d',
      formatDate: (date, format, locale) => {
        if (format === 'j M Y' && currentLang === 'th') {
          const day = date.getDate()
          const month = flatpickr.l10ns.th.months.shorthand[date.getMonth()]
          const year = date.getFullYear() + 543
          return `${day} ${month} ${year}`
        }
        return flatpickr.formatDate(date, format)
      },
      position: 'auto center'
      // onReady: function (selectedDates, dateStr, instance) {
      //   const btn = document.createElement('button')
      //   btn.type = 'button'
      //   btn.innerText = currentLang === 'th' ? 'เลือกวันนี้' : 'Today'
      //   btn.className =
      //     'w-full py-2 text-sm font-medium text-accent hover:bg-surface-1 transition-colors border-t border-line focus:outline-none'
      //   btn.addEventListener('click', function (e) {
      //     e.stopPropagation()
      //     instance.setDate(new Date())
      //     instance.close()
      //   })
      //   instance.calendarContainer.appendChild(btn)
      // }
    })
  })
}
