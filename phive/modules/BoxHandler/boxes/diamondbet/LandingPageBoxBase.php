<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class LandingPageBoxBase extends DiamondBox{
	
	function printHtml(){?>
		<script src="/phive/js/rainbows/rainbows.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="/phive/js/rainbows/rainbows.css" />
		<script>
			jQuery(document).ready(function(){
				rainbows.init({
					selector: '.go-rainbows',
					highlight: false,
					shadow: false,
					from: '#fffe7f',
					to: '#dcad3d'
				});
			});
		</script>
		<div class="landing-logo">
			<?php echo img("landing.".$this->getId().".logo", 280, 200) ?>
		</div>
		<div class="landing-character">
			<?php echo img("landing.".$this->getId().".character", 600, 410) ?>
		</div>
		<div class="landing-holder">
			<div class="upper-left-text go-rainbows">
				<?php et("landing.left.{$this->getId()}.top") ?>
			</div>
			<div class="landing-narrow-divider">
			
			</div>
			<div class="upper-left-text-big go-rainbows">
				<?php et("landing.left.{$this->getId()}.middle.top") ?>
			</div>
			<div class="upper-left-text-big go-rainbows">
				<?php et("landing.left.{$this->getId()}.middle.bottom") ?>
			</div>
			<div class="landing-narrow-divider">
			
			</div>
			<div class="upper-left-text-big go-rainbows">
				<?php et("landing.left.{$this->getId()}.bottom") ?>
			</div>
			<div class="landing-wide-divider">
			
			</div>
			<table>
				<col width="450"/>
				<col width="450"/>
				<tr>
					<td style="vertical-align: top; text-align: center;">
						<a class="bigbutton" style="float: middle; margin-right: 60px;" href="<?php echo phive('Localizer')->langLink('', '/?showlight=registration') ?>">
							<?php et('play.videoslots.now') ?>
						</a>
						<br clear="all" />
						<div class="arial-black-medium">
							<?php et("landing.left.{$this->getId()}.below.btn") ?>
						</div>
					</td>
					<td>
						<table class="landing-right-bottom-table" style="margin-left: 40px;">
							<tr>
								<td>
									<img src="/diamondbet/images/<?= brandedCss() ?>round1.png" />
								</td>
								<td>
									<span class="arial-black-big"><?php et("landing.right.instr.1") ?></span>
								</td>
							</tr>
							<tr>
								<td>
									<img src="/diamondbet/images/<?= brandedCss() ?>round2.png" />
								</td>
								<td>
									<span class="arial-black-big"><?php et("landing.right.instr.2") ?></span>
								</td>
							</tr>
							<tr>
								<td>
									<img src="/diamondbet/images/<?= brandedCss() ?>round3.png" />
								</td>
								<td>
									<span class="arial-black-big"><?php et("landing.right.instr.3") ?></span>
								</td>
							</tr>
							<tr>
								<td>
									<img src="/diamondbet/images/<?= brandedCss() ?>round4.png" />
								</td>
								<td>
									<span class="arial-black-big"><?php et("landing.right.instr.4") ?></span>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
	<?php }
}