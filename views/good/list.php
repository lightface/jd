<div align="center">
    <?php echo date('Y-m-d H:i:s',time()) ?>
    <?php echo '总记录数：'.$total ?>
    <br/>
    <hr/>
</div>

<!-- Table goes in the document BODY -->
<table>
    <tr>
        <th>ID</th>
        <th>名字</th>
        <th>价格</th>
        <th>最近价格</th>
        <th>添加时间</th>
        <th>更新时间</th>
    </tr>
    <?php
    foreach ($goods as $good) { ?>
        <tr>
            <td><?php echo $good['id'] ?></td>
            <td><a target="_blank" href="http://item.m.jd.com/product/<?php echo $good['id'] ?>.html"><?php echo $good['name'] ?></a></td>
            <td><?php echo $good['price_old'] ?></td>
            <td><?php echo $good['price_new'] ?></td>
            <td><?php echo date('Y-m-d H:i:s',$good['add_time']) ?></td>
            <td><?php echo $good['update_time'] ? date('Y-m-d H:i:s',$good['update_time']) : '' ?></td>
        </tr>
    <?php    }
    ?>
</table>
