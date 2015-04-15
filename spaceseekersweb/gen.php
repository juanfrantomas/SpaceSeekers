<?php 
$string = file_get_contents("data.json");
$json = json_decode($string, true);

 ?>
 <table border="0" cellpadding="0" cellspacing="0" >
 <?php foreach ($json as $row) {?>

<?php //var_dump($row); ?>

<tr>
<td><?php echo $row['name'] ?></td>
<td><?php echo $row['ra'] ?></td>
<td><?php echo $row['dec'] ?></td>
<td><?php echo $row['orbital-period'] ?></td>
<td><?php echo $row['status'] ?></td>
<td><?php echo $row['prob'] ?></td>
<td><a href="<?php echo $row['source'] ?>" target="_black">Source</a></td>

</tr>



<?php } ?></table>
