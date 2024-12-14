const section = document.querySelector('.blog-posts')
const slider = section.querySelector('.keen-slider')
const before = section.querySelector('.before')
const after = section.querySelector('.after')

let carousel

function initKeenSlider() {
	if (window.innerWidth < 512 && !carousel) {
		carousel = new KeenSlider(slider, {
			loop: true,
			slides: {
				perView: 1.15,
				spacing: 16,
			},
			breakpoints: {
				'(min-width: 512px)': {
					loop: false,
				},
			},
		})
	}
}

function destroyKeenSlider() {
	if (carousel) {
		carousel.destroy()
		carousel = null
	}
}

function handleResize() {
	if (window.innerWidth < 512) {
		initKeenSlider()
	} else {
		destroyKeenSlider()
	}
}

handleResize()

window.addEventListener('resize', handleResize)
