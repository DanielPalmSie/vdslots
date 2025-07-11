<?php 
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddAutoBonusPayoutEmailTemplate extends Seeder
{
	
	protected $mailTable;
	protected $localizedStringTable;
	protected $connection;

	protected array $data = [
		[
			'language' => 'en',
			'alias' => 'mail.bonus.auto.payout.subject',
			'value' => 'Welcome bonus reward ready for you!',
		],
		[
			'language' => 'en',
			'alias' => 'mail.bonus.auto.payout.content',
			'value' => "<p>
                        	<i>Greetings, __USERNAME__</i>
                        </p>
                    <p>
                        Your midweek just got better! We're thrilled to inform you that one of your five weekly 25 Free Spins welcome bonus rewards to play in Ave Caesar Dynamic Ways is ready and waiting for you within our Royal Gaming Palace.</p>
                    <p>
                    <p>
                    	Login and claim what's rightfully yours!
                    </p>
					<br />
					<p>Best Wishes</p>
					<p>The Royal Court of Kungaslottet</p>
                    "
		]
	];

	public function init()
	{
		$this->mailTable = 'mails';
		$this->localizedStringTable = 'localized_strings';
		$this->connection = DB::getMasterConnection();
	}
	
	/**
	 * Do the migration
	 */
	public function up()
	{
		$this->connection
			->table($this->mailTable)
			->insert([
				'mail_trigger' => 'bonus.auto.payout',
				'subject' => 'mail.bonus.auto.payout.subject',
				'content' => 'mail.bonus.auto.payout.content'
			]);

		$this->connection
			->table($this->localizedStringTable)
			->insert($this->data);
	}

	/**
	 * Undo the migration
	 */
	public function down()
	{
		$this->connection
			->table($this->mailTable)
			->where('mail_trigger', 'bonus.auto.payout')
			->delete();

		$this->connection
			->table($this->localizedStringTable)
			->whereIn('alias', ['mail.bonus.auto.payout.subject',
				'mail.bonus.auto.payout.content'])
			->delete();
	}
}