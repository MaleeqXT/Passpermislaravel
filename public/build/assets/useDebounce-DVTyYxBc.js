const n=(t=300)=>u=>{let e=null;return(...l)=>{e&&clearTimeout(e),e=setTimeout(()=>{e=null,u(...l)},t)}};export{n as u};
