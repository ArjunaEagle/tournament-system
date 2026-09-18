let bracketScale = 1;
const canvas = document.querySelector('.bracket-canvas');
function setZoom(value){bracketScale=Math.min(1.4,Math.max(.65,value));if(canvas)canvas.style.transform=`scale(${bracketScale})`;}
document.querySelector('[data-zoom="in"]')?.addEventListener('click',()=>setZoom(bracketScale+.1));
document.querySelector('[data-zoom="out"]')?.addEventListener('click',()=>setZoom(bracketScale-.1));
document.querySelector('[data-zoom="reset"]')?.addEventListener('click',()=>setZoom(1));
document.querySelector('[data-fullscreen]')?.addEventListener('click',()=>document.querySelector('.bracket-viewport')?.requestFullscreen());
const viewport=document.querySelector('.bracket-viewport');let down=false,startX,scrollLeft;
viewport?.addEventListener('pointerdown',e=>{down=true;startX=e.pageX;scrollLeft=viewport.scrollLeft;viewport.setPointerCapture(e.pointerId)});
viewport?.addEventListener('pointermove',e=>{if(down)viewport.scrollLeft=scrollLeft-(e.pageX-startX)});
viewport?.addEventListener('pointerup',()=>down=false);

