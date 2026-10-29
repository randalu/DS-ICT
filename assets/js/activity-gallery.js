(() => {
    'use strict';
    const galleries=[...document.querySelectorAll('[data-activity-gallery]')];if(!galleries.length)return;
    const dialog=document.createElement('dialog');dialog.className='activity-image-viewer';dialog.setAttribute('aria-label','Activity photographs');dialog.setAttribute('data-lightbox-ignore','');
    dialog.innerHTML='<div class="activity-image-viewer-toolbar"><span data-viewer-counter aria-live="polite"></span><button type="button" data-viewer-close aria-label="Close photographs">Close ×</button></div><div class="activity-image-viewer-frame"><button type="button" data-viewer-prev aria-label="Previous photograph">←</button><img alt=""><button type="button" data-viewer-next aria-label="Next photograph">→</button></div><p data-viewer-description></p>';
    document.body.append(dialog);
    const viewerImage=dialog.querySelector('img'),prev=dialog.querySelector('[data-viewer-prev]'),next=dialog.querySelector('[data-viewer-next]');
    viewerImage.draggable=false;
    let current=null,opener=null;
    function select(gallery,index){
        gallery.index=(index+gallery.slides.length)%gallery.slides.length;
        gallery.slides.forEach((slide,i)=>slide.hidden=i!==gallery.index);
        gallery.thumbnails.forEach((thumb,i)=>{if(i===gallery.index)thumb.setAttribute('aria-current','true');else thumb.removeAttribute('aria-current');});
        const counter=gallery.element.querySelector('[data-activity-counter]');if(counter)counter.textContent=(gallery.index+1)+' / '+gallery.slides.length;
    }
    function showViewer(){
        const slide=current.slides[current.index],img=slide.querySelector('img');
        viewerImage.src=slide.href;viewerImage.alt=img.alt;
        dialog.querySelector('[data-viewer-counter]').textContent=(current.index+1)+' / '+current.slides.length;
        dialog.querySelector('[data-viewer-description]').textContent=img.alt;
        prev.hidden=next.hidden=current.slides.length===1;
    }
    function move(direction){select(current,current.index+direction);showViewer();}
    function close(){dialog.close();}
    galleries.forEach(element=>{
        const gallery={element,slides:[...element.querySelectorAll('[data-activity-slide]')],thumbnails:[...element.querySelectorAll('[data-activity-thumb]')],index:0};
        gallery.thumbnails.forEach((thumb,index)=>thumb.addEventListener('click',event=>{event.preventDefault();select(gallery,index);}));
        gallery.slides.forEach(slide=>slide.addEventListener('click',event=>{
            event.preventDefault();current=gallery;opener=slide;showViewer();dialog.showModal();document.body.classList.add('activity-viewer-open');dialog.querySelector('[data-viewer-close]').focus();
        }));
    });
    prev.addEventListener('click',()=>move(-1));next.addEventListener('click',()=>move(1));dialog.querySelector('[data-viewer-close]').addEventListener('click',close);
    dialog.addEventListener('click',event=>{if(event.target===dialog)close();});
    dialog.addEventListener('close',()=>{document.body.classList.remove('activity-viewer-open');viewerImage.removeAttribute('src');const target=current?.slides[current.index]||opener;target?.focus();current=null;});
    dialog.addEventListener('keydown',event=>{
        if(event.key==='ArrowLeft'){event.preventDefault();move(-1);}
        if(event.key==='ArrowRight'){event.preventDefault();move(1);}
        if(event.key==='Tab'){
            const buttons=[...dialog.querySelectorAll('button')].filter(button=>!button.hidden),first=buttons[0],last=buttons[buttons.length-1];
            if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
            else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
        }
    });
    let pointer=null;
    viewerImage.addEventListener('pointerdown',event=>{pointer={x:event.clientX,y:event.clientY,id:event.pointerId};viewerImage.setPointerCapture(event.pointerId);});
    viewerImage.addEventListener('pointerup',event=>{
        if(!pointer||pointer.id!==event.pointerId)return;
        const dx=event.clientX-pointer.x,dy=event.clientY-pointer.y;pointer=null;
        if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy)&&current)move(dx<0?1:-1);
    });
    viewerImage.addEventListener('pointercancel',()=>pointer=null);
})();
