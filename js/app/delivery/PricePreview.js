import axios from 'axios'

const baseURL = location.protocol + '//' + location.hostname

// @see https://gist.github.com/anvk/5602ec398e4fdc521e2bf9940fd90f84

function asyncFunc(item, payload, token) {
  return new Promise((resolve, reject) => {

    $(item.element).find('.fa-spinner').removeClass('hidden')

    axios({
      method: 'post',
      url: `${baseURL}${item.pricingRule}/evaluate`,
      data: payload,
      headers: {
        Authorization: `Bearer ${token}`
      }
    })
      .then(response => {
        $(item.element).find('.fa-spinner').addClass('hidden')
        if (response.data.result === true) {
          $(item.element).addClass('list-group-item-success')
        } else {
          $(item.element).addClass('list-group-item-danger')
        }
        resolve(response.data)
      })
  })
}

function workMyCollection(items, payload, token) {
  return items.reduce((promise, current) => {
    return promise
      .then((previous) => {
        if (previous && previous.result === true) {
          return Promise.resolve(previous)
        }

        return asyncFunc(current, payload, token)
      })
      .catch(console.error)
  }, Promise.resolve())
}

class PricePreview {
  constructor(uris, token) {
    this.uris = uris
    this.token = token
  }
  update(delivery) {

    const $container = $('#delivery_price').closest('.delivery-price')

    $container.removeClass('delivery-price--error')
    $container.addClass('delivery-price--loading')
    $('#delivery_price_error').text('')
    $('#delivery_price').text((0).formatMoney(2, window.AppData.currencySymbol))

    $('#pricing-rules-debug li')
      .removeClass('list-group-item-success')
      .removeClass('list-group-item-danger')

    const pricingPromise = new Promise((resolve, reject) => {
      axios({
        method: 'post',
        url: baseURL + '/api/pricing/deliveries',
        data: delivery,
        headers: {
          Authorization: `Bearer ${this.token}`
        }
      })
        .then(response => resolve({ success: true, price: response.data }))
        .catch(e => {
          let message = ''

          if (e.response && e.response.status === 400) {
            if (e.response.data.hasOwnProperty('@type') && e.response.data['@type'] === 'hydra:Error') {
              $container.addClass('delivery-price--error')
              message = e.response.data['hydra:description']
            }
          }

          resolve({ success: false, message })
        })
    })
    const debugPromise = workMyCollection(this.uris, delivery, this.token)

    return Promise
      .all([ pricingPromise, debugPromise ])
      .then(values => {

        let [ priceResult, debugResult ] = values

        if (priceResult.success) {
          $('#delivery_price').text((priceResult.price / 100).formatMoney(2, window.AppData.currencySymbol))
        } else {
          $('#delivery_price_error').text(priceResult.message)
        }

        $container.removeClass('delivery-price--loading')
      })
  }
}

export default function(el, options) {

  const uris = $(el).find('ul li').map(function() {
    return {
      pricingRule: $(this).data('pricing-rule'),
      element: $(this),
    }
  }).toArray()

  return new PricePreview(uris, options.token)
}