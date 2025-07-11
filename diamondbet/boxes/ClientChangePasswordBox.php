<?php
require_once 'AccountBox.php';
class ClientChangePasswordBox extends AccountBox{
	
	function setup($route){
		return array($_SESSION['mg_username'], 'change-password');
	}
	
	function printHTML(){ 
		
		$uh = phive('UserHandler');
		
		if(!empty($_POST['submit'])){
			
			$req_fields 				= $uh->getReqFields(array('password2')); 
			$req_fields['password1'] 	= array('password', 'looseUsername', array(3));
			$err 						= $uh->validateUser(true, $req_fields);
			
			if(empty($err))
				$user_id = $uh->createUpdateUser(true, 'password1');
		}
		
		?>
		<div class="pad10">
		<form name="registerform" id="registerform" method="post" action="">
		    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	 		<?php $this->errorZone($err) ?>
	 		<div>
	 			<?php if(empty($err) && !empty($_POST['submit'])): ?>
	 				<?php et('password.changed.successfully') ?>	
	 			<?php endif ?>
	 		</div>
	 		<br/>
	 		<div>
	 			<?php et('register.changepassword.headline') ?>
	 		</div>
	 		<br/>
			<table class="registerform">
				<col width="200"/>
				<col width="250"/>
				<tr>
					<td><?php echo t('register.newpassword') ?></td>
					<td><?php dbInput('password1', htmlspecialchars($_POST['password1']), 'password') ?></td>
				</tr>
				<tr>
					<td><?php echo t('register.password2') ?></td>
					<td><?php dbInput('password2', htmlspecialchars($_POST['password2']), 'password') ?></td>
				</tr>
				
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="submit" name="submit" value="<?php echo t("register.update") ?>" class="submit"/>
						<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
					</td>
				</tr>
			</table>
	 	</form>
	 	</div>
	<?php }
	
}