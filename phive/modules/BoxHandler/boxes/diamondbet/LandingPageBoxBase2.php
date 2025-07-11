<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class LandingPageBoxBase2 extends DiamondBox{
	
	function printHtml(){
		$loc = phive('Localizer');
		?>
		<div class="landing-holder2">

			<table class="landing-table2">
				<col width="450"/>
				<tr>
					<td>
						<div class="landing-header2">
							<?php img("landing.".$this->getId().".character", 665, 37) ?>
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<center>
							<a class="landing-bigbtn2" href="?signup=true">
								<?php et('join.now') ?>
							</a>
							<div style="margin-bottom: 20px;"></div>
						</center>
					</td>
				</tr>
				<tr>
					<td>
						<div class="landing2-hr"></div>
					</td>
				</tr>
				<tr>
					<td>
						<center>
							<div class="vip-color" style="font-size: 20px; padding-top: 20px;">
								<?php et("landing2.".$this->getId().".sub.html") ?>
							</div>
							<div style="margin-bottom: 20px;"></div>
						</center>
					</td>
				</tr>
				<tr>
					<td>
						<div class="landing2-hr"></div>
					</td>
				</tr>
				<tr>
					<td>
						<center>
							<div style="font-size: 16px; padding-top: 40px;">
								<?php et("landing2.".$this->getId().".step1") ?>
							</div>
							<br/>
							<a href="?signup=true" class="vip-color" style="font-size: 16px; padding-top: 20px;">
								<?php et('join.now') ?>
							</a>
							<div style="margin-bottom: 20px;"></div>
						</center>
					</td>
				</tr>
				<tr>
					<td>
						<center>
							<div style="font-size: 16px; padding-top: 40px;">
								<?php et("landing2.".$this->getId().".step2") ?>
							</div>
							<div style="margin-bottom: 20px;"></div>
						</center>
					</td>
				</tr>
				<tr>
					<td>
						<center>
							<table>
								<tr>
									<td>
										<?php btnNormal(t('register.now'), "showRegistrationBox('/registration1/');") ?>
									</td>
									<td>
										<?php btnNormal(t('login'), "goTo('{$loc->langLink('', '/')}');") ?>
									</td>
								</tr>
							</table>
						</center>
					</td>
				</tr>
				<tr>
					<td>
						<center>
							<div style="font-size: 12px; padding-top: 20px;">
								<?php et("landing2.".$this->getId().".disclaimer") ?>
							</div>
							<div style="margin-bottom: 20px;"></div>
						</center>
					</td>
				</tr>
			</table>
			
		</div>
	<?php }
}