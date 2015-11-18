<div>测试</div>
<script>
    $(function(){
        var start = 757097;
        function getnums(){
            document.write('234');
            $.post('http://localhost/jd/web/index.php?r=good/grab-mut',{start:start,num:50});
            start = start + 50;
        }
        setInterval(getnums, 5000);
    });
</script>