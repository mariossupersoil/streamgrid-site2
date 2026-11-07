// StreamGrid cart. Keep these prices the same as config.php
// (config.php is what the checkout actually charges).
const SG_PRODUCTS = {
  single:    { name: 'The Single',     price: 3900 },
  pair:      { name: 'The Pair',       price: 6900 },
  household: { name: 'The Household',  price: 9500 },
  refill:    { name: 'Refill mat',     price: 2700 },
  clips:     { name: 'Spare clip set', price: 900 },
  'single-sub':    { name: 'Single subscription',    price: 3300, every: 3 },
  'pair-sub':      { name: 'Pair subscription',      price: 5900, every: 3 },
  'household-sub': { name: 'Household subscription', price: 8100, every: 3 },
};
const SG_SHIPPING = 1200, SG_FREE_OVER = 7500;

const money = c => '$' + (c / 100).toFixed(2);
let cart = [];
try { cart = JSON.parse(localStorage.getItem('sg_cart')) || []; } catch (e) { cart = []; }
cart = cart.filter(i => SG_PRODUCTS[i.id] && i.qty > 0);

function save() { try { localStorage.setItem('sg_cart', JSON.stringify(cart)); } catch (e) {} }
function add(id) {
  const line = cart.find(i => i.id === id);
  if (line) line.qty = Math.min(line.qty + 1, 10); else cart.push({ id, qty: 1 });
  save(); render(); openCart();
}
function setQty(id, qty) {
  cart = cart.map(i => i.id === id ? { ...i, qty: Math.max(0, Math.min(qty, 10)) } : i).filter(i => i.qty > 0);
  save(); render();
}

function render() {
  const count = cart.reduce((n, i) => n + i.qty, 0);
  document.querySelectorAll('[data-cart-count]').forEach(el => { el.textContent = count; el.hidden = count === 0; });
  const list = document.getElementById('cart-lines');
  const sub = cart.reduce((s, i) => s + SG_PRODUCTS[i.id].price * i.qty, 0);
  const recurring = cart.reduce((s, i) => s + (SG_PRODUCTS[i.id].every ? SG_PRODUCTS[i.id].price * i.qty : 0), 0);
  const ship = sub === 0 || recurring > 0 || sub >= SG_FREE_OVER ? 0 : SG_SHIPPING;
  list.innerHTML = cart.length ? '' : '<p class="cart-empty">Your cart is empty.</p>';
  cart.forEach(i => {
    const p = SG_PRODUCTS[i.id];
    const row = document.createElement('div');
    row.className = 'cart-line';
    row.innerHTML = `<div><strong>${p.name}</strong><span>${money(p.price)}${p.every ? ' every ' + p.every + ' months' : ''}</span></div>
      <div class="qty"><button aria-label="One less">−</button><span>${i.qty}</span><button aria-label="One more">+</button></div>`;
    const [minus, plus] = row.querySelectorAll('button');
    minus.onclick = () => setQty(i.id, i.qty - 1);
    plus.onclick = () => setQty(i.id, i.qty + 1);
    list.appendChild(row);
  });
  document.getElementById('cart-sub').textContent = money(sub);
  document.getElementById('cart-ship').textContent = sub === 0 ? '—' : (ship ? money(ship) : 'Free');
  document.getElementById('cart-total').textContent = money(sub + ship);
  const note = document.getElementById('cart-note');
  if (recurring) note.textContent = `Then ${money(recurring)} every 3 months. Ships free. Cancel anytime.`;
  else note.textContent = sub > 0 && sub < SG_FREE_OVER ? `Add ${money(SG_FREE_OVER - sub)} more for free shipping.` : (sub ? 'Your order ships free.' : '');
  document.getElementById('cart-total-label').textContent = recurring ? 'Due today' : 'Total';
  document.getElementById('checkout-btn').disabled = cart.length === 0;
}

const drawer = () => document.getElementById('cart');
function openCart()  { drawer().classList.add('open'); drawer().setAttribute('aria-hidden', 'false'); }
function closeCart() { drawer().classList.remove('open'); drawer().setAttribute('aria-hidden', 'true'); }

async function checkout() {
  const btn = document.getElementById('checkout-btn'), err = document.getElementById('cart-error');
  err.textContent = ''; btn.disabled = true; btn.textContent = 'One moment…';
  try {
    const r = await fetch('checkout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ items: cart }) });
    const data = await r.json();
    if (data.url) { window.location = data.url; return; }
    err.textContent = data.error || 'Checkout could not start.';
  } catch (e) {
    err.textContent = 'Checkout could not start. Please check your connection and try again.';
  }
  btn.disabled = false; btn.textContent = 'Checkout';
}

// One-time / Subscribe choice on each set card
function setupPlans() {
  document.querySelectorAll('[data-plan-card]').forEach(card => {
    const radios = card.querySelectorAll('input[type=radio]');
    const price = card.querySelector('.price'), save = card.querySelector('.save'), btn = card.querySelector('[data-add]');
    const update = () => {
      const subOn = card.querySelector('input[type=radio]:checked').value.endsWith('-sub');
      price.innerHTML = subOn ? card.dataset.subPrice + '<small> / 3 months</small>' : card.dataset.onePrice;
      save.textContent = subOn ? card.dataset.subSave : (card.dataset.oneSave || '\u00a0');
      btn.dataset.add = card.querySelector('input[type=radio]:checked').value;
      btn.textContent = subOn ? 'Subscribe' : 'Add to cart';
    };
    radios.forEach(r => r.addEventListener('change', update));
    update();
  });
}

document.addEventListener('DOMContentLoaded', () => {
  setupPlans();
  document.querySelectorAll('[data-add]').forEach(b => b.addEventListener('click', () => add(b.dataset.add)));
  document.querySelectorAll('[data-open-cart]').forEach(b => b.addEventListener('click', e => { e.preventDefault(); openCart(); }));
  document.querySelectorAll('[data-close-cart]').forEach(b => b.addEventListener('click', closeCart));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCart(); });
  document.getElementById('checkout-btn').addEventListener('click', checkout);
  render();
});
