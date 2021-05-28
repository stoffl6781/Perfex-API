<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); 
$attrs = (get_option('api_token') != '' ? array() : array('autofocus' => true)); ?>

<div id="wrapper">
   <div class="content">
      <div class="row">
      <?php echo form_open('api/settings/save', array('id' => 'settings_save-form')); ?>
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
                  <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                  <a href="<?php echo admin_url('api/settings/reset'); ?>" data-toggle="tooltip" data-title="<?php echo _l('api_settings_reset_info'); ?>" class="btn btn-default"><?php echo _l('reset'); ?></a>
               </div>
            </div>
         </div>
         <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body pickers">
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane ptop10 active" id="tab_items">
                           <div class="row">
                              <div class="col-md-12">
                                 <?php echo render_input('token', _l('api_token'), get_option('api_token'), 'text', $attrs); ?>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
             </div>
          </div>
      <?php echo form_close(); ?>
      </div>
   </div>
</div>
         

<?php init_tail(); ?>
</body>
</html>