<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [

    [
        'code' => '92423',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'MentalDX1.class',
                'hash' => '41211D02A2B95447C896D6E179FAF38C2DD40D3D',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'MentalDX1Symbols.class',
                'hash' => '58142B9474E044762FF5829EC497AF7CDA6294FF',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'MentalDX1Functions.class',
                'hash' => 'C1DCFAC8B6701F878AA36BCD604E57AAEDB1BE7F',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'MentalDX1Reels.class',
                'hash' => 'DF4AB6EAEB65903EF8FF530B3D0285F8F628D0F5',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96799',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'BarbarianFuryDX1Functions.class',
                'hash' => '76E8129EB66D4119251B552DEB4F86AA8E8574CD',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'BarbarianFuryDX1Reels.class',
                'hash' => 'C42BE228A6C0F3DCCA8221697685DCBE1F9F4928',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'BarbarianFuryDX1.class',
                'hash' => 'D51F3E0C69C56B4D029D59824BF7C7C900043481',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96800',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'BonusBunniesDX1.class',
                'hash' => '6A81291910E624A4D16B9F178C0E27639B4E702B',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'BonusBunniesDX1Functions.class',
                'hash' => '834836A3D8E93866DEE7CA5F75785CA3BE5F46D8',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BonusBunniesDX1Reels.class',
                'hash' => 'FE4AD9223C70CBA2735D3F6FB46E21E9D746B8CD',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96801',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'BookOfShadowsDX1LockedReels.class',
                'hash' => '3BAA998ACE93C30A3665FED28A0F25AA0AEBA065',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'BookOfShadowsDX1Reels.class',
                'hash' => '56FEFFE5A303177508169C28C4521E7785AAE829',
            ],
            [
                'name' => 'BookOfShadowsDX1Functions.class',
                'hash' => '57C610424963D4F0C97BAD95B1837A5B026323F0',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'BookOfShadowsDX1FSReelDraw.class',
                'hash' => '77FB951C83C782BE6A2F8FFE3042D4D314757A2E',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BookOfShadowsDX1.class',
                'hash' => 'FBF9C420072505D807D7EFEFB3A284CE2412AB8C',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96802',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'BuffaloHunterDX1Functions.class',
                'hash' => '5E3445C9725A923E0D1A8CDDA604521546584CDF',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'BuffaloHunterDX1.class',
                'hash' => '8B13F9B4401A35CC1D693D44D986979CEDEBCD5B',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'BuffaloHunterDX1Reels.class',
                'hash' => 'C34EDAB8B4C2EC798883DC7FD204A60C5C8BF5DF',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96803',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'BushidoWaysDX1Functions.class',
                'hash' => '017FE68B4B9D793B8121C86AE70DE1F8C71B1E28',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'BushidoWaysDX1Reels.class',
                'hash' => '80624C3D05EB3CA26E6A08B36356819DB01416EA',
            ],
            [
                'name' => 'BushidoWaysDX1.class',
                'hash' => '80915566EFDF3303335CDC36C3678ACDCED7BAE6',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96804',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'CasinoWinSpinDX1.class',
                'hash' => '1D6462B18C27F577F88D8E6411F4542414788D3E',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'CasinoWinSpinDX1Functions.class',
                'hash' => 'BF3F9CE8B3E6798CD52B87F15E5C69A1DC628234',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96805',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'CoinsOfFortuneDX1Functions.class',
                'hash' => '6025056B7C4061C0D0356E00F5A21A0442B68B86',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'CoinsOfFortuneDX1.class',
                'hash' => 'AC98D56CF3B64920D5EE1A7D8DAC90840384B682',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96806',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'DeadwoodDX1Reels.class',
                'hash' => '50FE016C81518DEB9F46C1A84C5BA0FF136A9B15',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'DeadwoodDX1.class',
                'hash' => 'FAD7D3EA4E95E20E3AF5048F694D0D294C9B4F0D',
            ],
            [
                'name' => 'DeadwoodDX1Functions.class',
                'hash' => 'FD4D158B27C9A75CF052A2E345B37F863B184E00',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96807',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'DragonTribeDX1Reels.class',
                'hash' => '04DB0B384D0A4F6FEBA4D077854B6633B6BB4561',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'DragonTribeDX1FreespinReels.class',
                'hash' => '5CD013E8208D149670F166F7296E1A78A8EC799D',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'DragonTribeDX1Functions.class',
                'hash' => '898DEDA010EE00AE78CE51E1F26F96172673D709',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'DragonTribeDX1.class',
                'hash' => 'A3E7597CEB330BF1751DE1FD22DA976E26469232',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'DragonTribeDX1FreespinExtremeReels.class',
                'hash' => 'FFDF0E443EA2C002CDA90BCBAE33F64268F744AE',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96808',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'DungeonQuestDX1.class',
                'hash' => '57181695B5EFCA9503465EE894A6690C4D43DBBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'DungeonQuestDX1Functions.class',
                'hash' => 'BBE373E26FBFD48893729458C5895930D9BB9979',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96809',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'EastVsWestDX1.class',
                'hash' => '5A4F1A35432E73DA0631F37E771E2FAB3B629943',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'EastVsWestDX1Reels.class',
                'hash' => '9ED081996C88518A82C90C6AE8350ECD2E38B91D',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'EastVsWestDX1Functions.class',
                'hash' => 'E7058173108487F35B77D58DACECD104795B6CC6',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96810',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'FireInTheHoleDX1.class',
                'hash' => 'A01420C2E7B0914DCD64CCAB0F170D9343981AFB',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'FireInTheHoleDX1Reels.class',
                'hash' => 'DFB2DC2EF1CE762C13E9ED2962C88FF1BE57DCC1',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'FireInTheHoleDX1Functions.class',
                'hash' => 'E9C8CCB3C06D0EB376C95271263784B0C59F333B',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96811',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'FruitsDX1.class',
                'hash' => '084FDD9142DF3F300D703411698CA060A89383BD',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'FruitsDX1Functions.class',
                'hash' => 'DF03358C57E9DED4119FE69DE3F6C90F0A25C2A3',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96812',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'GoldenGenieDX1Reels.class',
                'hash' => 'A360475BB52B1F753C77B069C3B34D49F5EDCDD3',
            ],
            [
                'name' => 'GoldenGenieDX1.class',
                'hash' => 'B6C29921995C11BD21BFA9799C25E3D8A51C689E',
            ],
            [
                'name' => 'GoldenGenieDX1Functions.class',
                'hash' => 'B8D8D1947A379455576095B9EB70137BFBDAB93B',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96813',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'HarlequinDX1.class',
                'hash' => '6479DF71C4EAC16F69CD2C24BA5E94EC5193D548',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'HarlequinDX1Functions.class',
                'hash' => '764F6D13F80F98ACDB9C4E3E85E4CE4914AD8937',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'HarlequinDX1Reels.class',
                'hash' => 'AC5D50D8EB931AE442571A27A126BF6AC20ED9C2',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96814',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'Hot4CashDX1.class',
                'hash' => '970B58B4933684DE2B6DCE8A98544A9448281987',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Hot4CashDX1Functions.class',
                'hash' => 'E4183BDE03AC3AC87DE1FBD06FE0A24D377027C8',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96815',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'HotNudgeDX1Functions.class',
                'hash' => '45098F8CA569CCCF9FBF35503B37201BAC678663',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'HotNudgeDX1.class',
                'hash' => 'E5E3691B4CC34F3BF6169CCCE8673707159DC7D9',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96817',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'ImmortalFruitsDX1Functions.class',
                'hash' => '46D126109D9CC12E49BF7B4340B0EE275F4AFB25',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'ImmortalFruitsDX1.class',
                'hash' => '8C9F7A8AFC8092AD0FFA962EFB7B275E01108E27',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96818',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'Infectious5DX1.class',
                'hash' => '41596E9ED9547F072A79796C91E710DA27600C60',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'Infectious5DX1Functions.class',
                'hash' => '99C4445398AAD69F55E16AA289CACFC2AB4C1BD1',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Infectious5DX1Reels.class',
                'hash' => 'DC16872AB85FD6C63498338C5B6560E428D2A069',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96819',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'KitchenDramaBbqFrenzyDX1Functions.class',
                'hash' => 'AB3249A304758D0294EC420BF04B90DBA7BDFBF3',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'KitchenDramaBbqFrenzyDX1.class',
                'hash' => 'C9F8432CDB6019318B8763B11A432FFDCAF3BB6F',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96820',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'ManhattanGoesWildDX1Functions.class',
                'hash' => 'B4DB8692C5419737B407D7660000BAD39EE7DD45',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'ManhattanGoesWildDX1.class',
                'hash' => 'E1663BE5F7A90220E19A8A50D1B43C342B88E268',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96821',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'MayanMagicDX1.class',
                'hash' => '1854F0F0052E929FBE23C1AB5522873024719AAB',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'MayanMagicDX1Functions.class',
                'hash' => '59B873C6781F8877FDCB1270F36699040EEE22BE',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96822',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'MilkyWaysDX1Functions.class',
                'hash' => '172EE6F6541B1F75ECAE20464ED53FC0E4B8DD64',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'MilkyWaysDX1Reels.class',
                'hash' => '8785AD914A7DE8F0EC253A3848B3D891912965C3',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'MilkyWaysDX1.class',
                'hash' => 'A2B66148B02C4CFFCC73B9ABB87F3889BBF651F7',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96823',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'MonkeysGoldDX1.class',
                'hash' => '0EBF646DAE53D9F94EFA4E9EEEF8A6DA7B7F527F',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'MonkeysGoldDX1Functions.class',
                'hash' => 'A18676D98E1CAC89F38BE9C4459409FFBCE831AE',
            ],
            [
                'name' => 'MonkeysGoldDX1Reels.class',
                'hash' => 'ACD888848AF27682AA0D208AE05D20536E1824FF',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96824',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'OwlsDX1.class',
                'hash' => 'AAC67024D84729BDC1D9946963BB87BB14A62585',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'OwlsDX1Functions.class',
                'hash' => 'D6C97ACB0BD68F319BAFB20B13B9D7377266146E',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96825',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PixiesVsPiratesDX1.class',
                'hash' => '4B3B618C4B539456E38A5134B4A14366894C9997',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'PixiesVsPiratesDX1Reels.class',
                'hash' => '5BCFE95FFC339664FF2147BCD189C6066242DF96',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'PixiesVsPiratesDX1Functions.class',
                'hash' => 'B4396B7A2AEE0257462D266607F3096007CA96BB',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96826',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PoisonEveDX1.class',
                'hash' => '4A4086845C858F2A3ECFC5E581D401F3F32F0D7E',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'PoisonEveDX1Functions.class',
                'hash' => '870E986EEB3A27591C81387E3744FF57ACF26A2C',
            ],
            [
                'name' => 'PoisonEveDX1Reels.class',
                'hash' => '9354B9F9C4EA7E84E272B7A69CAC8AEF3AF80B9B',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96827',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'PunkRockerDX1FeatureBuyReels.class',
                'hash' => '3760DE15F14E08E05865812085ECD63961742D3C',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PunkRockerDX1.class',
                'hash' => '46F22E3BFAFFAE376F535B0F0FC18037A2655C8A',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'PunkRockerDX1Functions.class',
                'hash' => 'DBF2E5802F05C45D0426DC323543EF2E7BF7E865',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'PunkRockerDX1Reels.class',
                'hash' => 'FC1DD5CE093F919DD4F235B372DFDC58E9AA68A1',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96828',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'SanQuentinDX1Reels.class',
                'hash' => '134CF0B82781D6E8E0295AED37DD617BC289EE6B',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'SanQuentinDX1.class',
                'hash' => '42716DF22ADA67DDA3172CB650DE3CF68C060D81',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'SanQuentinDX1Functions.class',
                'hash' => '550DF6A0A1BC1785CB4819BB59FCB038AE43B036',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96829',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'TeslaJoltDX1Functions.class',
                'hash' => 'A7A08E0AB2A66AF1C75CD713988A1C978B29E106',
            ],
            [
                'name' => 'TeslaJoltDX1.class',
                'hash' => 'C064F1FAA1E05C1E38988E1CC6F22EFDDF7F74F7',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96830',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'ThorDX1.class',
                'hash' => '7FEA9D1A532ED7A23308E70E89C626C3C6D63507',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'ThorDX1Functions.class',
                'hash' => 'C38E7B03C0CD894EB2A13BDC8A7C2A64E88CF48C',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96831',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'TombOfAkhenatenDX1Functions.class',
                'hash' => '91661A80351A8634DD6036FE2AACF6F5AAD8E090',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'TombOfAkhenatenDX1.class',
                'hash' => 'DD98CAA954EDD909268D35D6203BD57F1982E328',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'TombOfAkhenatenDX1Reels.class',
                'hash' => 'FBFC3CB7A55929CE654216790E4BAF2469B6A523',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96832',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'TombOfNefertitiDX1Functions.class',
                'hash' => '2B06C2097AFEC96985C6F36E5CEAF9C483BAB1A2',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'TombOfNefertitiDX1.class',
                'hash' => '77C8E0D638D78CA127B34FDEB0D14DB8D4870B78',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96833',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'TombstoneDX1.class',
                'hash' => 'BB3CACBBD00BE51D4DDFDAB609563FDFE60DAFAD',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'TombstoneDX1Functions.class',
                'hash' => 'FBAE5FDFE406D20632B93CD9F9268698B41BAA52',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96834',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'TractorBeamDX1.class',
                'hash' => '68DEC852E93B59308C4481990D226844E74C7094',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'TractorBeamDX1Functions.class',
                'hash' => 'D94BA25A5567EF1F2A7AF29DC895CB947CC5AF49',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96835',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'WarriorGraveyardDX1Functions.class',
                'hash' => '10D0C1A5ADA0401527D565EADC9371CB400D6706',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'WarriorGraveyardDX1Reels.class',
                'hash' => '8E2645FF083CA37E21AC859B825AA0B7397F26DE',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'WarriorGraveyardDX1.class',
                'hash' => 'C2045403E0F1B89DF0560FC9E1DD072DF5B0A27C',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96836',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'WixxDX1Functions.class',
                'hash' => '2764C4311E1533995507D85269B87CFEAA685BF5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'WixxDX1.class',
                'hash' => 'E32C40166A491F791764DF5A6D6ED679CBB12645',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96837',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'XwaysHoarderDX1.class',
                'hash' => '14A44841BA63D40A32BAF482181BED10FBF1A1C7',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'XwaysHoarderDX1Functions.class',
                'hash' => 'C50C17B54573F6837B83F3A884712E8BF26FE4F8',
            ],
            [
                'name' => 'XwaysHoarderDX1Reels.class',
                'hash' => 'C8532F817554D964D438EBB2CEE4A1D0DE59C384',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96838',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'DasxBootDX1Functions.class',
                'hash' => '15B1B71B2E186F399CC77E15B7755EDA80DB12CA',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'DasxBootDX1.class',
                'hash' => '7457A08DEDF85209F3834518C22614FDE9E2BA5E',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'DasxBootDX1Reels.class',
                'hash' => 'F78595CE7811BAD75F4AC8DC2BC3F936B66E8B34',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96839',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'EvilGoblinsDX1.class',
                'hash' => '559EAFC71F247A35095748519B38D30A5920CAB8',
            ],
            [
                'name' => 'EvilGoblinsDX1FreespinReels.class',
                'hash' => '5A805583210547D5DE9798FA5D73389875D2345D',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'EvilGoblinsDX1Reels.class',
                'hash' => '9D00F06DD8FA97725285B836D055BE59BDC9887D',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'EvilGoblinsDX1Functions.class',
                'hash' => 'B526CD3EA41963F8CF5C76A6E17407E5A0288677',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'EvilGoblinsDX1TrainReels.class',
                'hash' => 'D89A9F0F5A821CA42130B3066A4FA229FA121128',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'EvilGoblinsDX1SuperFreespinReels.class',
                'hash' => 'DF18E68A168F94F285E238630AC82ACC05D9C9D9',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96840',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'LegionXDX1Reels.class',
                'hash' => '43DBE03EDE522D1374F7F829F26299A9F00874C8',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'LegionXDX1Functions.class',
                'hash' => '8A4C8DA8424FA68FA9192C89E2AB87DB5109C0B3',
            ],
            [
                'name' => 'LegionXDX1.class',
                'hash' => '8CA67FD2FFA7C45FD8277EAC2727DEA1D5AA712C',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96841',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'TrueGritDX1FreespinExtremeReels.class',
                'hash' => '12A7A28BBA40B6AB0BCF6DA9060725E5DB86EE16',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'TrueGritDX1TrainReels.class',
                'hash' => '609F59D82FD3B3AFC7B3D94303E38752E0EBE9EF',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'TrueGritDX1Functions.class',
                'hash' => 'B04C565B8ACAF391099B4D912E604DFB53F1559D',
            ],
            [
                'name' => 'TrueGritDX1.class',
                'hash' => 'C845329DAFF90F110B79A584987E46EB2E3C3501',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'TrueGritDX1Reels.class',
                'hash' => 'D3F9A9696F6EF0BD2BDEF340EEDB4F9CFD9505B0',
            ],
            [
                'name' => 'TrueGritDX1FreespinReels.class',
                'hash' => 'D9018485AFC604494EB1EBF414C29869CC9F5C92',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96842',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'TombstoneRipDX1Functions.class',
                'hash' => '8259D56BF57847952A2C45C8503BFD76F3E0849C',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'TombstoneRipDX1.class',
                'hash' => 'D754F72D15526B6920D917C8727A768FD380C0C1',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'TombstoneRipDX1Reels.class',
                'hash' => 'E73810ADD46F5F36F159D7A2A28ED5E01C122733',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96843',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'ModelDX1.class',
                'hash' => '048DE9715ABDF98E66D82766EFB0E076DE729DC8',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'PunkToiletDX1.class',
                'hash' => '2CC70E6444344F65149AE979F8F9EBE742452DBC',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'PunkToiletDX1Functions.class',
                'hash' => 'FE38C14777A82F866E18C44F2155C7B4DEC8ADBB',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96844',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'MiseryMiningDX1Functions.class',
                'hash' => '13CD3B0AF9EB9D7CD1C8338117939946B3F7C5B3',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'MiseryMiningDX1.class',
                'hash' => '421DCA6458989A111774AD9B0802745361F69D0D',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'MiseryMiningDX1Reels.class',
                'hash' => '6B8A0B7FBD587A10181DAEB2437692221A70BDAE',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'MiseryMiningDX1TrainReels.class',
                'hash' => '977C5C6AEA6E2C3EA0954529EECD5BB1C49C5F8C',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96845',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'RememberGulagDX1Functions.class',
                'hash' => '60CCAF1E229C9BDFAADF7065A7C4F888C1CA2F9E',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'ModelDX1.class',
                'hash' => '7B9F0A285EAC8B7A508A4BEE2926D566AD319DC1',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'RememberGulagDX1.class',
                'hash' => 'E4479AB2A8026E7F166094F14C67F5141BB737D5',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96846',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'KarenDX1Functions.class',
                'hash' => '8374BCA8920D0C29A6975EC494260B52F52324B3',
            ],
            [
                'name' => 'KarenDX1Reels.class',
                'hash' => '949D7682CBD921D50FB65813A0E37EC93F259F71',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'KarenDX1.class',
                'hash' => 'EA9D842B3E6B65F84A92711DCF9EC641BEF9B69D',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96847',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'FolsomPrisonDX1Functions.class',
                'hash' => '9D72410350A51DDDE039F2A4F83231BB306AF2EF',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'FolsomPrisonDX1Reels.class',
                'hash' => 'A7AA876E24A72AF4F4C1C47CA479682EB62E996B',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'FolsomPrisonDX1.class',
                'hash' => 'E5FA224DB932E59BEDD8229F718DF6F450FB4B67',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96848',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'TheRaveDX1.class',
                'hash' => '378E4B9C33E5D069C865A92C4D7B41C1592F5FEA',
            ],
            [
                'name' => 'ModelDX1.class',
                'hash' => '3CE6A00DBBBF53BE70032645BF57230A30112074',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'TheRaveDX1Functions.class',
                'hash' => '42CE625720312A98C3AAF86C7A1F3FEECBEF6BD3',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '96849',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RoadRageDX1Functions.class',
                'hash' => '068B330307FDACA67ED6E7CFED469E52481D26CF',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'RoadRageDX1.class',
                'hash' => '383D3227BB068BA81E595BCA05B64072D8402A37',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'RoadRageDX1Reels.class',
                'hash' => '705DBDA558D51D54D5B655479697BD8E8B62127E',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
        ]
    ],
    [
        'code' => '132084',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'LittleBighornDX1Reels.class',
                'hash' => '741B34664EC32890B822D30FF4A46644ED72A0E8',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'LittleBighornDX1.class',
                'hash' => 'BE59E63EA9249A4FB45A93B44E1EEF5C1DD3EEF3',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'LittleBighornDX1Functions.class',
                'hash' => 'D7537041972474279E4491E0CBD6B4FE99D9E25F',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132087',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'ModelDX1.class',
                'hash' => 'A049310F763CC9929F88620E4A71A8CA2F4E1242',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'SerialDX1Functions.class',
                'hash' => 'ADC1D4485F3B509662268FE8F8C6A32E362F51C1',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'SerialDX1.class',
                'hash' => 'EF3AB2C1BBB8F2EE3E2DC69B45B23929EE2CDA5B',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132089',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RockBottomDX1.class',
                'hash' => '1C33C80479894C74875BB94FE20D4507DF1CD3DD',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'RockBottomDX1Reels.class',
                'hash' => '4B4A73AFA9BD852D2A009897ADF0871B930B2B40',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RockBottomDX1Functions.class',
                'hash' => 'C8BE29C839B4145C588C9D8B2E1D9951A2A2A8A5',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132091',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'PearlHarborDX1Functions.class',
                'hash' => 'AE39B4AB80B2DC4A47520546FED77195EB0B3CDA',
            ],
            [
                'name' => 'PearlHarborDX1.class',
                'hash' => 'AE4973335F7EED5B0D774D9FDA55A458F9704404',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'PearlHarborDX1Model.class',
                'hash' => 'EA2B3A727BEF5BADEF5323EEDACE26DFE0585819',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132093',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'ModelDX1.class',
                'hash' => '67BAAD28ACF5009416B02BEE53ACAB17D82A110C',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'DeadCanaryDX1.class',
                'hash' => 'E2F6E7EDA487820219F9B510FA047D6F0DB3F5FD',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'DeadCanaryDX1Functions.class',
                'hash' => 'ECE9A9D7465F4D6748E8929BD61DAFFE63A8FE09',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132096',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'WalkOfShameDX1.class',
                'hash' => 'A32C6EDDD16B608297C95628B0C8F3AC6DA06F64',
            ],
            [
                'name' => 'WalkOfShameDX1Functions.class',
                'hash' => 'C3636F51FD5F0A6DAFE98DA4432A31472B936423',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'WalkOfShameDX1Reels.class',
                'hash' => 'DE4D1514A77AEC621EC2DEE7E7EA7209032EF4ED',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132098',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'BenjiKilledInVegasDX1Reels.class',
                'hash' => '3D75BE18550820F572001A5464FD72330F237077',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'BenjiKilledInVegasDX1.class',
                'hash' => 'B5DC3459BDF9AADE5EB6FDE69FC554B35EB2E73A',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'BenjiKilledInVegasDX1Functions.class',
                'hash' => 'D93FDF09EA5971375A6B71E643FE20395A1A9C3E',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132100',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'BloodAndShadowDX1.class',
                'hash' => '7AA94878057F51F7F13C8035DC42E0AB0D73680C',
            ],
            [
                'name' => 'BloodAndShadowDX1Functions.class',
                'hash' => '825A494E27436EF9B7F11D632A6D47264DFC3206',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'BloodAndShadowDX1Reels.class',
                'hash' => 'CACFE8694B5EA567DF1CC0A4404BB75F78E70EF5',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132105',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'DisturbedDX1Reels.class',
                'hash' => '40703DDD06D3FA0E8DD7933B715391B6260F566B',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'DisturbedDX1Functions.class',
                'hash' => '4CB55E03398C1CB988F1D36597BD4D7F040D4D6A',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'DisturbedDX1.class',
                'hash' => '624A607BF91FECFE277FF5193E59BECEB5304CE1',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132107',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'ModelDX1.class',
                'hash' => '202C33A4C0A9D92A3FCA357ACC77D6199D896FC2',
            ],
            [
                'name' => 'WhackedDX1.class',
                'hash' => '231527450BAEEFDCF2E47422BB57E6C1E550C088',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'WhackedDX1Functions.class',
                'hash' => '6112E769C412B420FE3C8DB3CE971614879F867F',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132109',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'GluttonyDX1Functions.class',
                'hash' => '2817DCDAE33E9E294FDB69A0704BD450D9F7E3B5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'GluttonyDX1.class',
                'hash' => 'B3BF8B08BC68BA5608DF54E23795B0D02D693CAE',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'GluttonyDX1Reels.class',
                'hash' => 'FF072DB40284C69A7ACFBBEDF1AFB6191141B9D7',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132115',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'TheCageDX1.class',
                'hash' => '55FDCB99F390A4F312C42E3B3EA111144C31BCD5',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'ModelDX1.class',
                'hash' => 'B0F1C2369E1AACC38D6B0134125F2E92039F69CA',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'TheCageDX1Functions.class',
                'hash' => 'CB4A719FAB0AECE3C97A7F37E8793C1890E16AE8',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132118',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'BountyHuntersDX1Functions.class',
                'hash' => '395D2CED8492D6CBACE50C04FD27912C68D3ADDB',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'BountyHuntersDX1.class',
                'hash' => '867BFD9C1315FE0F8490BA58D7B0181937A3846C',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'BountyHuntersDX1Reels.class',
                'hash' => 'E00B1C80CA9D1131BB29F9BDD0A8C1BCE69453B9',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132120',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'DjPsychoDX1Model.class',
                'hash' => '31A69BB8477546862B5579D8AFBD5CD3BF531A04',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'DjPsychoDX1ModelGolden.class',
                'hash' => '8F83C89F711A5C45DF7C7F6CB693BA2E14512469',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'DjPsychoDX1Functions.class',
                'hash' => 'B659346442E599E92FD9D97B1F62A3B84BDD2A71',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'DjPsychoDX1.class',
                'hash' => 'DD2446214A082598FA8B29BE1CDC4C265CDFC5AF',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132122',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'TrueKultDX1Functions.class',
                'hash' => 'A0FD73D99251C092CC33F8BB5630576D4A65FE2F',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'ModelDX1.class',
                'hash' => 'ED7CD38C2074E149B6254F1BBBD34F6D4F59DE8D',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'TrueKultDX1.class',
                'hash' => 'F9F899E656A4EDFD9C38207BA17F9595540F77BC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132125',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RandomCallLogger.class',
                'hash' => '03AA5A6F6AA8F57D060B397E4AAF82A9620A49E4',
            ],
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'RandomGeneratorWrapper.class',
                'hash' => '0C5AB2987EE8EA726D7067C05FD0A841B5FFE83D',
            ],
            [
                'name' => 'LoggingInvocationHandler.class',
                'hash' => '12282CC84F27D94E95CD9F08ECB3F2E6CBF52E50',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'LoggingRandomProvider.class',
                'hash' => '41D976CEF6A55B1A290D0DD726FA6B09461F8A68',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'TheCryptDX1Reels.class',
                'hash' => '5C142BCE2A7D694120BFB86D1F0E5AF314015929',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'TheCryptDX1Functions.class',
                'hash' => 'DDFBDD9FFF0D39529420C540C81ECABCCB1640A1',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'TheCryptDX1FreespinReels.class',
                'hash' => 'F34D0921D566DBC715A1A89B5B5D6E991B24649F',
            ],
            [
                'name' => 'TheCryptDX1.class',
                'hash' => 'F37E09DF50EAD1067FAB5F2E9B8B583D8982F642',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ],
    [
        'code' => '132196',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'RNGHealthCheck.class',
                'hash' => '052E3DD23756A7BBBF543ACB311FD2023FF54B9D',
            ],
            [
                'name' => 'ElPasoDX1Reels.class',
                'hash' => '0C9AABE93FA66048A912BBB7506A65821B1F3ED1',
            ],
            [
                'name' => 'GarbageCollectorEntropySource.class',
                'hash' => '1A2138E7204BB79B48E12B736F35648C2EA4A24A',
            ],
            [
                'name' => 'RandomProvider.class',
                'hash' => '1DB38DCC032114F28DBCE1B74D282602A67DB7E5',
            ],
            [
                'name' => 'ElPasoDX1.class',
                'hash' => '2546B3AF3E3A18C220DD88E4694813E9F05B338A',
            ],
            [
                'name' => 'UptimeEntropySource.class',
                'hash' => '296EF2E47E36167BD07605D93E8E233ADA59A214',
            ],
            [
                'name' => 'URandomEntropySource.class',
                'hash' => '2997A9DDAB615739EE37B01F3AD8B495156C6317',
            ],
            [
                'name' => 'Counter.class',
                'hash' => '3EC7CCFDDAB3CB706AAAA871AAD6CB049D1E63C7',
            ],
            [
                'name' => 'EventAdder.class',
                'hash' => '414B95991D7F42316401F72C3B56940106CEDC59',
            ],
            [
                'name' => 'BackgroundCyclingRunnable.class',
                'hash' => '421B04AA190129FA08BE58E68244F7D508A41923',
            ],
            [
                'name' => 'PrefetchingSupplier.class',
                'hash' => '53A4EA99F7E489AB3C52CF9DA0BEF2AD9F355E18',
            ],
            [
                'name' => 'Accumulator.class',
                'hash' => '59AFA1AA2E0ABB0BC728056F04DAE9BB1D4EFFBC',
            ],
            [
                'name' => 'Util.class',
                'hash' => '638245CC0A9CE628D6A526DE55E01D9E107D8028',
            ],
            [
                'name' => 'RandomDataBuffer.class',
                'hash' => '6E6A100D5CD8599820BE17478C016DF404C43BC9',
            ],
            [
                'name' => 'Dump.class',
                'hash' => '6FB8BAB5C04FE557B538555C4228EE5CE83B052C',
            ],
            [
                'name' => 'EventAdderImpl.class',
                'hash' => '714150E43D00F167BE8A317F2D66BAC15FF8724A',
            ],
            [
                'name' => 'Fortuna.class',
                'hash' => '770AB27F22F92EF50A2BE8487EB6922D95F61585',
            ],
            [
                'name' => 'SchedulingEntropySource.class',
                'hash' => 'A154A916A77E7D3018153CCA1D1A344858FEB386',
            ],
            [
                'name' => 'RNGHealthCheck$SegmentSupplier.class',
                'hash' => 'C9B6A6E65332C93BA15D9BC8C0B0D315171AA71E',
            ],
            [
                'name' => 'ThreadTimeEntropySource.class',
                'hash' => 'C9B74028EB833848903B21BB257004D6426E16AC',
            ],
            [
                'name' => 'Fortuna$1.class',
                'hash' => 'CBC33AC11ED7B90FD78D6EA49293DD65E4660098',
            ],
            [
                'name' => 'EntropySource.class',
                'hash' => 'D3D8DFA2A895AB9027530025170F1F19963AADDC',
            ],
            [
                'name' => 'MemoryPoolEntropySource.class',
                'hash' => 'DA22F35DDCD4F1F61A2230BE9BE125B44A318142',
            ],
            [
                'name' => 'Generator.class',
                'hash' => 'DCCA45B5572DD2A58E874CFC349ACCA4618592E9',
            ],
            [
                'name' => 'Pool.class',
                'hash' => 'DD9AF082EBD7B5A8CEC8C8E54EA850199AA3686C',
            ],
            [
                'name' => 'Rijndael.class',
                'hash' => 'E36E48F393F0567EF48B9674F33F7C4787C365CB',
            ],
            [
                'name' => 'ElPasoDX1Functions.class',
                'hash' => 'EA809C3AE88B5276C1D2597F8B05F3176C6CF847',
            ],
            [
                'name' => 'Encryption.class',
                'hash' => 'ED0601596F5078925A86ADB0830FBB6143003878',
            ],
            [
                'name' => 'FreeMemoryEntropySource.class',
                'hash' => 'EE015DBC075D1A7CA1EC5183182242227D46C195',
            ],
            [
                'name' => 'LoadAverageEntropySource.class',
                'hash' => 'EF8F4E52D7DDC782AA7F3896A51A92778C4D5E54',
            ],
            [
                'name' => 'EventScheduler.class',
                'hash' => 'F5EFA7F3DC10B2C9EC4EB401ED992B9A9AE0DFAC',
            ],
            [
                'name' => 'BufferPoolEntropySource.class',
                'hash' => 'FF7ED0A18EA8F099BCCBDBAA0E436CB5129F4A15',
            ],
        ]
    ]

];

foreach ($games as $game) {
    $i = 0;
    if (!empty($game['software_modules'])) {
        foreach ($game['software_modules'] as $module) {
            $software_modules[$i]['lun_nome_modulo_critico'] = strlen($game['software_modules'][$i]['name']);
            $software_modules[$i]['name_critical_module'] = $game['software_modules'][$i]['name'];
            $software_modules[$i]['hash_critical_module'] = $game['software_modules'][$i]['hash'];
            $i++;
        }


    } else {
        $software_modules = [
            [
                'name_critical_module' => $game['name'],
                'hash_critical_module' => $game['hash']
            ]
        ];
    }
    if (!empty($game['cert_ver'])) {
        $cert_vers = $game['cert_ver'];
    } else {
        $cert_vers = 1;
    }

    $payload = [
        'game_code' => 0,
        'game_type' => 0,
        'cod_element_type' => 2,
        'cod_element' => $game['code'],
        'prog_cert_version' => $cert_vers,
        'prog_sub_cert_version' => 0,
        'ctr_lista_moduli_sw' => count($software_modules),
        'software_modules' => $software_modules
    ];

    unset($software_modules);

    $response = lic('installedSoftwareVersionCommunication', [$payload], $user);

    if ($response['code'] != 0) {
        print_r($payload);
        print_r($response);
    }

    usleep(500);
}

echo "Script complete";
