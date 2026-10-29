'use strict';
(() => {const {config:c,api,message}=Quote;
    document.getElementById('conversion-form')?.addEventListener('submit',async ev=>{ev.preventDefault();const form=ev.currentTarget;form.querySelector('button').disabled=true;try{const data=new FormData(form);await api('convert',{id:c.id,option:c.option,customer_id:Number(data.get('customer_id')||0),replace_reviewed:data.has('replace_reviewed')});location.reload();}catch(err){message(err.message,true);form.querySelector('button').disabled=false;}});
    document.getElementById('guest-accept-form')?.addEventListener('submit',async ev=>{ev.preventDefault();const form=ev.currentTarget;form.querySelector('button').disabled=true;try{await api('guest_accept',{plan_id:c.plan,evidence:{...Object.fromEntries(new FormData(form)),policy_snapshot_hash:c.hash,revision_id:c.id,option_key:c.option}});location.reload();}catch(err){message(err.message,true);form.querySelector('button').disabled=false;}});
})();
