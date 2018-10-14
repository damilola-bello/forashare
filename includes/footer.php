<?php
  // Close the database connection.
  mysqli_close($dbc);
  unset($dbc);

?>  
    <script src="javascript/main.js"></script>
    <footer class="page-footer">
    	<ul class="page-footer-content row">
    		<li><a href="about.php">About</a></li>
    		<li class="footer-separator">&ndash;</li>
    		<li><a href="#">Contact</a></li>
    	</ul>
    	<span class="copyright-details">Copyright &copy;<?php echo "<script>var d = new Date(); document.write(d.getFullYear()); </script>"; ?></span>
    </footer>
  </body>
</html>