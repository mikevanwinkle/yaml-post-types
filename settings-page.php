<div class="wrap">
    <h1><?php _e('YAML Config Settings:', $td); ?></h1>
    <p><?php _e('AML Config allows you to load custom post types, custom fields, custom taxonomies and p2p relationships via YAML config files instead of a GUI and database configuration. This allows for easier setup and cleaner deployments</p>', $td); ?></p>
    
    <h2><?php _e('Current Saved Config:', $td); ?></h2>
    <p><?php _e('For performance reasons the config loaded from files is saved in a transient. If you make changes to the config file you may need to clear the transient before the new configuration will take effect', $td); ?></p>
    <form action="tools.php" method="GET">
        <p class="submit">
           <input type="hidden" name="page" value="yaml-config" />
           <input type="submit" name="refresh" id="submit" class="button button-primary" value="Refresh Configuration">
        </p>
     </form>
     
    <pre><?php foreach($configs as $idx => $config): ?>
<hr/>
Filename: <?php echo $files[$idx]; ?><br/>
---
<?php echo $dumper->dump($config, 5); ?>
<br/>
<?php endforeach; ?>       
</pre>
</div>

