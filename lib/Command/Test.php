<?php

declare(strict_types=1);


/**
 * Nextcloud - Related Resources
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\RelatedResources\Command;


use OC\Core\Command\Base;
use OCA\Circles\CirclesManager;
use OCA\RelatedResources\Service\RelatedService;
use OCA\RelatedResources\Tools\Traits\TStringTools;
use OCP\IUserManager;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Create
 *
 * @package OCA\RelatedResources\Command
 */
class Test extends Base {
	use TStringTools;

	private IUserManager $userManager;
	private OutputInterface $output;
	private RelatedService $relatedService;


	/**
	 * @param IUserManager $userManager
	 * @param RelatedService $relatedService
	 */
	public function __construct(
		IUserManager $userManager,
		RelatedService $relatedService
	) {
		parent::__construct();

		$this->userManager = $userManager;
		$this->relatedService = $relatedService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('related:test')
			 ->setDescription('returns related resource to a share')
			 ->addArgument('userId', InputArgument::REQUIRED, 'user\'s point of view')
			 ->addArgument('providerId', InputArgument::REQUIRED, 'Provider Id (ie. files)')
			 ->addArgument('itemId', InputArgument::REQUIRED, 'Item Id');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws \OCA\RelatedResources\Exceptions\RelatedResourceProviderNotFound
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('userId');
		$providerId = $input->getArgument('providerId');
		$itemId = $input->getArgument('itemId');

		$user = $this->userManager->get($userId);
		if (is_null($user)) {
			throw new InvalidArgumentException('must specify a valid local user');
		}

		$userId = $user->getUID();

		/** @var CirclesManager $circleManager */
		$circleManager = \OC::$server->get(CirclesManager::class);
		$circleManager->startSession($circleManager->getLocalFederatedUser($userId));


		if ($input->getOption('output') !== 'json') {
			$this->displayRecipients($providerId, $itemId, ($input->getOption('output') === 'json'));
		}
		$this->displayRelated($providerId, $itemId, ($input->getOption('output') === 'json'));

		return 0;
	}


	private function displayRecipients(string $providerId, string $itemId, bool $json): void {
		$result = $this->relatedService->getSharesRecipients($providerId, $itemId);

		$output = new ConsoleOutput();
		if ($json) {
			$output->writeln(json_encode($result, JSON_PRETTY_PRINT));

			return;
		}

		$output = $output->section();
		$table = new Table($output);
		$table->setHeaders(
			[
				'Single Id',
				'User Type',
				'User Id',
				'Source'
			]
		);

		$table->render();
		foreach ($result as $entry) {
			$table->appendRow(
				[
					$entry->getSingleId(),
					$entry->getUserType(),
					$entry->getUserId(),
					$entry->getBasedOn()->getSource()
				]
			);
		}

		$output->writeln('');
	}


	private function displayRelated(string $providerId, string $itemId, bool $json): void {
		$result = $this->relatedService->getRelatedToItem($providerId, $itemId);

		$output = new ConsoleOutput();
		if ($json) {
			$output->writeln(json_encode($result, JSON_PRETTY_PRINT));

			return;
		}

		$output = $output->section();
		$table = new Table($output);
		$table->setHeaders(
			[
				'Provider Id',
				'Item Id',
				'Title',
				'Description',
				'Score',
				'Link'
			]
		);

		$table->render();
		foreach ($result as $entry) {
			$table->appendRow(
				[
					$entry->getProviderId(),
					$entry->getItemId(),
					$entry->getTitle(),
					$entry->getSubtitle(),
					$entry->getScore(),
					$entry->getUrl()
				]
			);
		}

		$output->writeln('');
	}

}

