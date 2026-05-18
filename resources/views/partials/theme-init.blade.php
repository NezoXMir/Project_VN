{{-- Runs synchronously before any rendering to prevent flash of wrong theme --}}
<script>
(function(){
    try{
        var t=localStorage.getItem('theme')||'system';
        if(t==='dark'||(t==='system'&&window.matchMedia('(prefers-color-scheme:dark)').matches))
            document.documentElement.classList.add('dark');
    }catch(e){}
})();
</script>
