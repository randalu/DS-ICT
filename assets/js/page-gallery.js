(() => {'use strict';
 document.querySelectorAll('[data-page-gallery]').forEach(root=>{
  if(root.dataset.pgReady)return;root.dataset.pgReady='1';const links=[...root.querySelectorAll('[data-pg-photo]')],grid=root.querySelector('.page-gallery-grid'),dialog=root.querySelector('dialog'),image=root.querySelector('[data-pg-view-image]'),counter=root.querySelector('[data-pg-counter]');let index=0,opener=null,touch=null;
  const mobile=matchMedia('(max-width:1023px)');const wrap=n=>(n+links.length)%links.length;
  const show=n=>{index=wrap(n);const photo=links[index].querySelector('img');image.src=links[index].href;image.alt=photo.alt;root.querySelector('[data-pg-description]').textContent=photo.alt;root.querySelector('[data-pg-view-counter]').textContent=(index+1)+' / '+links.length;};
  const open=(n,trigger)=>{opener=trigger;show(n);dialog.showModal();};
  const close=()=>{dialog.close();opener?.focus();};
  const move=n=>{index=wrap(n);if(dialog.open)show(index);else {grid.scrollTo({left:links[index].offsetLeft-grid.offsetLeft,behavior:matchMedia('(prefers-reduced-motion:reduce)').matches?'auto':'smooth'});counter.textContent=(index+1)+' / '+links.length;}};
  links.forEach((a,i)=>a.addEventListener('click',e=>{e.preventDefault();open(i,a);}));root.querySelector('[data-pg-all]').addEventListener('click',e=>open(index,e.currentTarget));root.querySelector('[data-pg-close]').addEventListener('click',close);dialog.addEventListener('cancel',e=>{e.preventDefault();close();});dialog.addEventListener('click',e=>{if(e.target===dialog)close();});
  for(const [selector,delta] of [['[data-pg-prev]',-1],['[data-pg-next]',1],['[data-pg-view-prev]',-1],['[data-pg-view-next]',1]]){const button=root.querySelector(selector);button.hidden=links.length<2;button.addEventListener('click',()=>move(index+delta));}
  root.addEventListener('keydown',e=>{if(e.key==='Tab'&&dialog.open){const buttons=[...dialog.querySelectorAll('button')].filter(b=>!b.hidden&&!b.disabled),first=buttons[0],last=buttons[buttons.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}return;}if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();move(index+(e.key==='ArrowLeft'?-1:1));}});
  grid.addEventListener('scroll',()=>{if(mobile.matches){index=Math.max(0,Math.min(links.length-1,Math.round(grid.scrollLeft/grid.clientWidth)));counter.textContent=(index+1)+' / '+links.length;}},{passive:true});
  image.addEventListener('touchstart',e=>{touch=[e.changedTouches[0].clientX,e.changedTouches[0].clientY];},{passive:true});image.addEventListener('touchend',e=>{if(!touch)return;const dx=e.changedTouches[0].clientX-touch[0],dy=e.changedTouches[0].clientY-touch[1];if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy)*1.4)move(index+(dx<0?1:-1));touch=null;},{passive:true});
  root.querySelector('.page-gallery-mobile').hidden=!mobile.matches;mobile.addEventListener('change',()=>{root.querySelector('.page-gallery-mobile').hidden=!mobile.matches;});
 });
})();
