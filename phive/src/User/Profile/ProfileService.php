<?php

declare(strict_types=1);

namespace Videoslots\User\Profile;

use GmsSignupUpdateBoxBase;
use Laraphive\Domain\User\DataTransferObjects\ProfileData;

final class ProfileService
{
    /**
     * @var \GmsSignupUpdateBoxBase
     */
    private GmsSignupUpdateBoxBase $box;

    /**
     * @param \GmsSignupUpdateBoxBase $box
     */
    public function __construct(GmsSignupUpdateBoxBase $box)
    {
        $this->box = $box;
    }

    /**
     * @return array
     */
    public function getProfileData(): array
    {
        $result = [];
        $user = $this->box->cur_user ?? cu();

        $data = lic('getPrintDetailsData', [$user], $user);
        $fields = lic('getPrintDetailsFields', [], $user);

        if (p('view.affiliate.info')) {
            $fields[] = 'bonus_code';
            $affiliate_user = cu($data['affe_id']);

            if (is_object($affiliate_user)) {
                $affiliate_username = $affiliate_user->getUsername();
            }
        }

        foreach ($fields as $field) {
            $item = $this->filterData($field, $data);

            if (! is_null($item)) {
                $result[] = $item;
            }
        }

        if (p('view.affiliate.info')) {
            $item = $this->filterData('affiliate', ['affiliate' => $affiliate_username, 'id' => $data['id']]);

            if (! is_null($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param string $field
     * @param array $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\ProfileData|null
     */
    private function filterData(string $field, array $data): ?ProfileData
    {
        if (in_array($field, ['country', 'city']) && in_array($data['country'], ['AU', 'NZ'])) {
            return null;
        }

        $user_id = $data['id'];
        $title = 'account.' . $field;
        $value = $this->box->obfuscateData($field, $data);

        return new ProfileData((int) $user_id, $field, $title, $value);
    }
}
