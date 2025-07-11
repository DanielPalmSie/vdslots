<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForFooterSection extends SeederTranslation
{
    private array $VS_data = [
        'en' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">This website videoslots.com when offering casino games is operated by Videoslots Limited, a company incorporated in Malta with registration number C49090 and its registered address at The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, and when offering sportsbook by Videoslots Sports Limited, a company incorporated in Malta with registration number C93953 and its registered address at The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta.</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited is licenced and regulated by the Malta Gaming Authority under licence number <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1" target="_blank">MGA/CRP/258/2014</a> issued on the 19th of May 2020 and the Gambling Commission in Great Britain under account number <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>. The facilities provided in the UK are solely made in reliance on the latter licence.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com/mobile/responsible-gaming/#underage-gaming"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Warning: Gambling may be addictive.",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. All rights reserved.</p>'
        ],
        'sv' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">N&auml;r casinospel erbjuds drivs webbplatsen Videoslots.com av Videoslots Limited, ett f&ouml;retag med s&auml;te p&aring; Malta med registreringsnummer C49090 och den registrerade adressen The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. N&auml;r sportsbook erbjuds drivs webbplatsen av Videoslots Sports Limited, ett f&ouml;retag med s&auml;te p&aring; Malta med registreringsnummer C93953 och den registrerade adressen The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta.</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited &auml;r licensierat av Spelinspektionen i Sverige under licensnumret <a href="https://www.spelinspektionen.se/sok-licens/">23Si1181</a>, utf&auml;rdat 1 januari 2024 och giltigt till 31 december 2028, som endast tillhandah&aring;lls spelare fr&aring;n Sverige.</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Sport Limited &auml;r licensierat av Spelinspektionen i Sverige under licensnumret <a href="https://www.spelinspektionen.se/sok-licens/">19Si3549</a>, utf&auml;rdat 3 september 2020 till 2 september 2025, som endast tillhandah&aring;lls spelare fr&aring;n Sverige.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com//sv/mobile/responsible-gaming/#spel-for-vuxna"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/spelinspektionen_logo.png",
            "link": "https://www.spelinspektionen.se/"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Spelande kan vara beroendeframkallande. Spela ansvarsfullt.<br/>Om du beh&ouml;ver support i f&ouml;rh&aring;llande till dina spelvanor hittar du mer information p&aring;",
            "links": [
            {"text":"St&ouml;dlinjen.se", "link":"https://stodlinjen.se/"}]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Alla r&auml;ttigheter f&ouml;rbeh&aring;llna.</p>'
        ],
        'br' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Este site videoslots.com, ao oferecer jogos de cassino, &eacute; operado pela Videoslots Limited, uma empresa constitu&iacute;da em Malta com o n&uacute;mero de registro C49090 e seu endere&ccedil;o registrado em The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, e ao oferecer apostas esportivas pela Videoslots Sports Limited, uma empresa constitu&iacute;da em Malta com o n&uacute;mero de registro C93953 e seu endere&ccedil;o registrado em The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta.</p>
<p id="middle" style="color: #63686c; text-align: center;">A Videoslots Limited &eacute; licenciada e regulamentada pela Malta Gaming Authority sob o n&uacute;mero de licen&ccedil;a <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1" target="_blank">MGA/CRP/258/2014</a> emitido em 19 de maio de 2020 e pela Gambling Commission na Gr&atilde;-Bretanha sob o n&uacute;mero de contaa <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>. As instala&ccedil;&otilde;es fornecidas no Reino Unido s&atilde;o feitas exclusivamente com base na &uacute;ltima licen&ccedil;a.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com//br/mobile/responsible-gaming/#jogos-para-menores"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advert&ecirc;ncia: Apostar pode ser viciante.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.gambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Todos os direitos reservados.</p>'
        ],
        'cl' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Este sitio web videoslots.com cuando ofrece juegos de casino es operado por Videoslots Limited, una empresa constituida en Malta con n&uacute;mero de registro C49090 y su domicilio social en The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, y cuando ofrece apuestas deportivas por Videoslots Sports Limited, una empresa constituida en Malta con n&uacute;mero de registro C93953 y su domicilio social en The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited est&aacute; autorizada y regulada por la Autoridad del Juego de Malta con el n&uacute;mero de licencia <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1" target="_blank">MGA/CRP/258/2014</a> emitida el 19 de mayo de 2020 y por la Comisi&oacute;n del Juego de Gran Breta&ntilde;a con el n&uacute;mero de cuenta <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>. Las instalaciones proporcionadas en el Reino Unido se realizan &uacute;nicamente en dependencia de esta &uacute;ltima licencia.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com/cl/mobile/responsible-gaming/#ujuegos-menores"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advertencia: Apostar puede ser adictivo.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.gambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Todos los derechos reservados.</p>'
        ],
        'da' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Denne hjemmeside Videoslots.com er drevet af Videoslots Limited, et firma baseret p&aring; Malta, med registreringsnummer C49090, og den registrerede adresse The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Videoslots Limited er reguleret af Spillemyndigheden i Danmark under licensnummer <a href="https://www.spillemyndigheden.dk/en/licence-holder/videoslots-limited" target="_blank">18-0650512</a>, gyldig til og med d. 29. august 2024, udstedt af De Danske Spillemyndigheder, der f&oslash;rer tilsyn med driften af spiltjenester.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/footer_logo_spillemyndigheden.png",
            "link": "https://www.spillemyndigheden.dk/en/licence-holder/videoslots-limited"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/footer_logo_18plus.png",
            "link": "https://www.videoslots.com/da/mobile/responsible-gaming/#mindreariges-spil"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/footer_logo_ludomani.png",
            "link": "https://ludomani.dk/"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/footer_logo_stopspillet.png",
            "link": "https://www.stopspillet.dk/"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advarsel: Gambling kan v&aelig;re vanedannende.",
            "links": [
            {"text":"StopSpillet", "link":"https://www.stopspillet.dk/"},
            {"text":"Center for Ludomani", "link":"https://ludomani.dk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">Videoslots.com 2011 - 2024. Alle rettigheder forbeholdes.</p>'
        ],
        'de' => [
            'app.footer.license_detail.html' => '<div id="middle" style="color: #4e4848; text-align: center;"><span style="color: #4e4848;">Die Website Videoslots.com wird von Videoslots Limited betrieben, einer Firma in Malta, mit Registernummer C49090, eingetragen und registrierter Adresse im The Space Level 2 &amp; 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Videoslots Limited wird von der maltesischen Gl&uuml;cksspielbeh&ouml;rde (MGA) unter der am 19. Mai 2020 ausgestellten&nbsp;Lizenznummer&nbsp;</span><span style="color: #4e4848;"><a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&amp;company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&amp;details=1" target="_blank" rel="noopener noreferrer">MGA/CRP/258/2014</a>&nbsp;und der Gambling Commission unter der Kontonummer&nbsp;</span><span style="color: #4e4848;"><a href="https://registers.gamblingcommission.gov.uk/39380" target="_blank" rel="noopener noreferrer">39380</a>&nbsp;lizensiert und reguliert. Die Bereitstellung von Diensten f&uuml;r UK Spieler geschieht ausschlie&szlig;lich durch die letztgenannte Lizenz.&nbsp;</span><span style="color: #4e4848;">Videoslots Limited wird von Spelinspektionen in Schweden unter der Lizenznummer&nbsp;</span><span style="color: #4e4848;"><a href="https://www.spelinspektionen.se/" target="_blank" rel="noopener noreferrer">18Li7373</a>&nbsp;lizenziert, ausschlie&szlig;lich f&uuml;r Spieler aus Schweden. Videoslots Limited wird auch von Spillemyndigheden in D&auml;nemark unter der Lizenznummer&nbsp;</span><span style="color: #4e4848;"><a href="https://www.spillemyndigheden.dk/en/licence-holder/videoslots-ltd" target="_blank" rel="noopener noreferrer">18-0650512</a>&nbsp;lizenziert, g&uuml;ltig bis 29. August 2024, ausschlie&szlig;lich f&uuml;r Spieler aus D&auml;nemark.&nbsp;Videoslots Limited (Steuernummer: 91405930370 - maltesische Umsatzsteuernummer: MT22142721) ist in Italien von der Agenzia delle Dogane e dei Monopoli (ADM) unter der Konzessionsnummer 15427 lizenziert und reguliert, die am 16. Oktober 2019 erteilt wurde.</span></div>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&amp;company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&amp;details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/39380"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/spelinspektionen_logo.png",
            "link": "https://www.spelinspektionen.se/"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/spillemyndigheden_logo.png",
            "link": "https://www.spillemyndigheden.dk/en/licence-holder/videoslots-ltd"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/ADM-logo.png",
            "link": "https://www.adm.gov.it/portale/monopoli/giochi/normativa/gioco-legale-e-responsabile"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/Regulator.png",
            "link": "https://www.adm.gov.it/portale/it/web/guest/home/"
            }            
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Warnung: Gl&uuml;cksspiel kann s&uuml;chtig machen.&nbsp;",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ],
            "logos": "[
            {
            "image": "https://www.videoslots.com/file_uploads/footer-over-18s-only.png",
            "link": "https://www.videoslots.com/de/mobile/responsible-gambling/#underage-gaming"
            }
            ]"
            }',
            'app.footer.copyright.html' => '<div id="middle">&copy; Videoslots.com 2011 - 2023 All rights reserved.</div>'
        ],
        'es' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Este sitio web videoslots.com cuando ofrece juegos de casino es operado por Videoslots Limited, una empresa constituida en Malta con n&uacute;mero de registro C49090 y su domicilio social en The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, y cuando ofrece apuestas deportivas por Videoslots Sports Limited, una empresa constituida en Malta con n&uacute;mero de registro C93953 y su domicilio social en The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited est&aacute; autorizada y regulada por la Autoridad del Juego de Malta con el n&uacute;mero de licencia <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1" target="_blank">MGA/CRP/258/2014</a> emitida el 19 de mayo de 2020 y por la Comisi&oacute;n del Juego de Gran Breta&ntilde;a con el n&uacute;mero de cuenta <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>. Las instalaciones proporcionadas en el Reino Unido se realizan &uacute;nicamente en dependencia de esta &uacute;ltima licencia.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com/es/mobile/responsible-gaming/#juegos-menores"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advertencia: Apostar puede ser adictivo.",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Todos los derechos reservados.</p>'
        ],
        'fi' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c;">T&auml;t&auml; verkkosivustoa videoslots.com yll&auml;pit&auml;&auml; kasinopelej&auml; tarjotessaan Videoslots Limited, joka on rekister&ouml;ity Maltalla rekister&ouml;intinumerolla C49090 ja jonka rekister&ouml;ity osoite on The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, ja urheiluvedonly&ouml;nti&auml; tarjotessaan Videoslots Sports Limited, joka on rekister&ouml;ity Maltalla rekister&ouml;intinumerolla C93953 ja jonka rekister&ouml;ity osoite on The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta.</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited on lisensoitu ja s&auml;&auml;nnelty Maltan peliviranomaisen toimiluvalla <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1" target="_blank">MGA/CRP/258/2014</a>, joka on my&ouml;nnetty 19. toukokuuta 2020, ja Ison-Britannian Gambling Commissionin toimiluvalla <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>. Isossa-Britanniassa tarjottavat toiminnot perustuvat yksinomaan j&auml;lkimm&auml;iseen lisenssiin.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com/fi/mobile/responsible-gaming/#alaikaisten-pelaamisen-estaminen"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Varoitus: Uhkapelaaminen voi aiheuttaa riippuvuutta.",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Kaikki oikeudet pid&auml;tet&auml;&auml;n.</p>'
        ],
        'hi' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">इस वेबसाइट Videoslots.com का संचालन वीडियोसलॉट्स लिमिटेड द्वारा किया गया है, जो माल्टा में रजिस्ट्रेशनसंख्या C49090 के साथ शामिल है और The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta। वीडियोस्लॉट्स लिमिटेड को लाइसेंस संख्या के तहत माल्टा गेमिंग प्राधिकरण द्वारा लाइसेंस और माल्टा गेमिंग अथॉरिटी के नीचे विनियमित किया जाता है&nbsp;<a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1" rel="noopener noreferrer" target="_blank">MGA/B2C/258/2014</a>&nbsp;1 अगस्त 2018 को जारी किया गया&nbsp; और गैंबलिंग कमीशन के तहत यूनाइटेड किंगडम में जुआ आयोग&nbsp;<a href="https://registers.gamblingcommission.gov.uk/39380" rel="noopener noreferrer" target="_blank">39380</a>.यूके के खिलाड़ियों को दी जाने वाली सुविधाएं पूरी तरह से बाद के लाइसेंस पर निर्भरता में बनाई गई हैं। वीडियोस्लॉट्स लिमिटेड द्वारा लाइसेंस संख्या के तहत स्वीडन में स्पेलिन्सपेकशनेन द्वारा लाइसेंस प्राप्त किया जाता है <a href="https://www.spelinspektionen.se/" rel="noopener noreferrer" target="_blank">18Li7373</a>&nbsp;सिर्फ स्वीडन के खिलाड़ियों के लिए पूरी तरह से प्रदान की जाती है, और लाइसेंस संख्या के तहत स्पिलिमींडीघेन (डानिश जुआ प्राधिकरण) द्वारा डेनमार्क में लाइसेंस और विनियमित भी है।&nbsp;<a href="https://www.spillemyndigheden.dk/en/licence-holder/videoslots-ltd" rel="noopener noreferrer" target="_blank">18-0650512</a>,29 अगस्त 2024 तक वैध है। स्पिल्मीइंडहेडेन डेनमार्क में जुआ सेवाओं के संचालन का पर्यवेक्षण करता है। वीडियोस्लॉट्स लिमिटेड (वित्तीय कोड: 91405930370 - मोलतिज़ वैट क्रमांक: MT22142721) को लाइसेंस संख्या के तहत Agenzia delle Dogane e dei Monopoli&nbsp; (ADM) द्वारा के नीचे विनियमित किया जाता है 15427 16 अक्टूबर&nbsp; 2019 को जारी किया गया।&nbsp;</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com/hi/responsible-gaming/"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "चेतावनी: जुआ व्यसनी हो सकता है&nbsp;",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. All rights reserved.</p>'
        ],
        'it' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Videoslots.it &egrave; gestito da Videoslots Limited, compagnia con sede a Malta, registrata con il numero C49090 presso l\'indirizzo The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta.</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited (Codice Fiscale: 91405930370 - P.Iva Maltese: MT22142721) &egrave; regolamentata in Italia dall\'Agenzia delle Dogane e dei Monopoli. Concessione n.15427 rilasciata il 16 ottobre 2019.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.it/file_uploads/18+.png",
            "link": "https://www.videoslots.it/mobile/responsible-gaming/#gioco-protetto-sicuro"
            },
            {
            "image": "https://www.videoslots.it/file_uploads/adm_logo.png",
            "link": "https://www.adm.gov.it/portale/monopoli/giochi/normativa/gioco-legale-e-responsabile"
            },
            {
            "image": "https://www.videoslots.it/file_uploads/agenziadoganemonopoli_logo.png",
            "link": "https://www.adm.gov.it/portale/it/home"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Attenzione: Il gioco pu&ograve; causare dipendenza.&nbsp;",
            "links": [
            {"text":"Istituto Superiore di Sanit&agrave;", "link":"https://www.iss.it/il-gioco-d-azzardo"}            
            ],
            "phone" => "&nbsp;- Numero Verde 800 55 8822"
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Tutti i diritti riservati.</p>'
        ],
        'no' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Dette nettstedet videoslots.com drives av Videoslots Limited, et selskap registrert p&aring; Malta med registreringsnummer C49090 og registrert adresse p&aring; The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, n&aring;r det tilbyr casinospill, og av Videoslots Sports Limited, et selskap registrert p&aring; Malta med registreringsnummer C93953 og registrert adresse p&aring; The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, n&aring;r det tilbyr sportsbook.</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited er lisensiert og regulert av Malta Gaming Authority under lisensnummer <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1" target="_blank">MGA/CRP/258/2014</a> utstedt den 19. mai 2020 og Gambling Commission i Storbritannia under kontonummer <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>. Fasilitetene som tilbys i Storbritannia er utelukkende basert p&aring; sistnevnte lisens.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com/no/mobile/responsible-gaming/#mindrearige-som-spiller"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advarsel: Gambling kan v&aelig;re avhengighetsskapende.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Alle rettigheter forbeholdt.</p>'
        ],
        'on' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited is a registered agent of iGaming Ontario as of 1st June 2023, licensed and regulated by the Alcohol and Gaming Commission of Ontario under registration number <a href="https://www.iagco.agco.ca/prod/pub/en/Default.aspx?PossePresentation=Default&PosseObjectId=145646045" target="_blank">OPIG1267597</a>, having its registered address at The Space, Level 2&3, Triq Alfred Craig, Pieta, Malta PTA1320.</p>
<p id="middle" style="color: #63686c; text-align: center;">This website is for the use of adults 19 years of age or older in the Province of Ontario.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/19plus_logo.png",
            "link": "https://www.videoslots.com/mobile/responsible-gaming/#preventing-underage-gambling"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/igamingontario_logo.png",
            "link": "https://igamingontario.ca/en"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/connexontario_logo.png",
            "link": "https://www.connexontario.ca/en-ca/"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Gambling can be addictive. Play responsibly.<br/> 19+ Please contact",
            "links": [
            {"text":"ConnexOntario", "link":"https://www.connexontario.ca/en-ca/"}            
            ],
            "phone": "at 1-866-531-2600 to speak to an advisor, free of charge."
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots Ltd 2024. All rights reserved.</p>'
        ],
        'pe' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Este sitio web videoslots.com cuando ofrece juegos de casino es operado por Videoslots Limited, una empresa constituida en Malta con n&uacute;mero de registro C49090 y su domicilio social en The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, y cuando ofrece apuestas deportivas por Videoslots Sports Limited, una empresa constituida en Malta con n&uacute;mero de registro C93953 y su domicilio social en The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta</p>
<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited est&aacute; autorizada y regulada por la Autoridad del Juego de Malta con el n&uacute;mero de licencia <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1" target="_blank">MGA/CRP/258/2014</a> emitida el 19 de mayo de 2020 y por la Comisi&oacute;n del Juego de Gran Breta&ntilde;a con el n&uacute;mero de cuenta <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>. Las instalaciones proporcionadas en el Reino Unido se realizan &uacute;nicamente en dependencia de esta &uacute;ltima licencia.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.videoslots.com/pe/mobile/responsible-gaming/#juegos-menores"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advertencia: Apostar puede ser adictivo.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}            
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Todos los derechos reservados.</p>'
        ],
        'dgoj' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center; width: 100%;">Juego Autorizado: Este sitio web es operado por Videoslots Casino plc, una compa&ntilde;&iacute;a establecida en Malta, registrada con el n&uacute;mero C89361 y con domicilio legal en Ewropa Business Centre, Level 3, Suite 701, Dun Karm Street, Birkirkara BRK 9034, Malta. Videoslots Casino plc cuenta con licencia y est&aacute; registrada por la Direcci&oacute;n General de Ordenaci&oacute;n del Juego (DGOJ). <a href="https://www.ordenacionjuego.es/es/op-VideoslotsCasino" target="_blank">Videoslots es titular de las siguientes licencias otorgadas por la DGOJ:</a> Licencia general Apuestas, con n&uacute;mero de inscripci&oacute;n GA/2018/026; Licencia general Otros Juegos, con n&uacute;mero de inscripci&oacute;n 365/GO/1083; Licencia singular M&aacute;quinas de Azar, con n&uacute;mero de inscripci&oacute;n MAZ/2020/070; Licencia singular Ruleta, con n&uacute;mero de inscripci&oacute;n RLT/2020/039 y Licencia singular Black Jack, con n&uacute;mero de inscripci&oacute;n BLJ/2020/035.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/file_uploads/18+.png",
            "link": "https://www.videoslots.com/mobile/responsible-gaming/"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/autoprohibicion_logo.png",
            "link": "https://www.ordenacionjuego.es/es/rgiaj"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/jugarbien_logo.png",
            "link": "https://www.jugarbien.es/"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/Juego-Seguro-logo.png",
            "link": "https://www.juegoseguro.es/"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Aviso: Apostar puede ser adictivo.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}            
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Videoslots.com 2011 - 2024. Todos los derechos reservados.</p>'
        ],
        'ja' => [
            'app.footer.license_detail.html' => '<div id="middle" style="color: #4e4848; text-align: center;"><span style="color: #4e4848;"><span style="color: #4e4848;">当社のウェブサイトVideoslots.comは、マルタで設立されたVideoslots Limited（登録番号C49090）によって運営されています。Videoslots Ltd.の登録住所はThe Space Level 2 &amp; 3, Alfred Craig Street, Pieta, PTA 1320 Maltaです。2020年5月19日に発行されたライセンス番号</span><span style="color: #4e4848;"><a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&amp;company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&amp;details=1" target="_blank" rel="noopener noreferrer">MGA/CRP/258/2014</a>のもとでマルタの賭博委員会&ldquo;Malta Gaming Authority&rdquo;によって認可および規制されている会社です。また、英国では、アカウント番号<a href="https://registers.gamblingcommission.gov.uk/39380" target="_blank" rel="noopener noreferrer">39380</a>のもとでイギリスの賭博委員会&ldquo;UK Gambling Commission&rdquo;によって認可および規制を受けています。 英国のプレイヤーには後者のライセンスのみが有効です。</span><span style="color: #4e4848;">Videoslots Limitedはライセンス番号</span><span style="color: #4e4848;"><a href="https://www.spelinspektionen.se/" target="_blank" rel="noopener noreferrer">18Li7373</a>のもとでスウェーデンの&rdquo;Spelinspektionen&rdquo;によって認可されています。このライセンスはスウェーデンのプレイヤーにのみ有効です。また、賭博サービスの運営を監督するデンマークの賭博委員会によって発行されたライセンス番号</span><span style="color: #4e4848;"><a href="https://www.spillemyndigheden.dk/en/licence-holder/videoslots-ltd" target="_blank" rel="noopener noreferrer">18-0650512</a>（2024年8月29日まで有効）</span><span style="color: #4e4848;">のもとでデンマークの&rdquo;Spillemyndigheden&rdquo;によって認可されています。Videoslots Limited （財務コード: 91405930370 -マルタのVAT番号／付加価値税登録番号: MT22142721）は、イタリアで2019年10月16日に発行されたライセンス番号15427の下、Agenzia delle Dogane e dei Monopoli (ADM)によって認可および規制されています。</span></span></div>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&amp;company=ff43afe7-05e6-4e58-92fa-f2bccbd973bc&amp;details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/39380"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/spelinspektionen_logo.png",
            "link": "https://www.spelinspektionen.se/"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/spillemyndigheden_logo.png",
            "link": "https://www.spillemyndigheden.dk/en/licence-holder/videoslots-ltd"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/ADM-logo.png",
            "link": "https://www.adm.gov.it/portale/monopoli/giochi/normativa/gioco-legale-e-responsabile"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/Regulator.png",
            "link": "https://www.adm.gov.it/portale/it/web/guest/home/"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "警告：ギャンブルは中毒性があるかもしれません。",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"},
            "logos": "[
            {
            "image": "https://www.videoslots.com/file_uploads/footer-over-18s-only.png",
            "link": "https://www.videoslots.com/ja/mobile/responsible-gambling/#underage-gaming"
            }            
            ]            
            }',
            'app.footer.copyright.html' => '<div id="middle">&copy; Videoslots.com 2011 - 2023. All rights reserved.</div>'
        ],
    ];

    private array $MV_data = [
        'en' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">This website mrvegas.com is operated in the United Kingdom, by Videoslots Limited, a Malta based company, with registration number C49090, and registered address at the Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, licenced and regulated by the Great Britain Gambling Commission in the United Kingdom under license number <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>.</p>
<p id="middle" style="color: #63686c; text-align: center;">The website mrvegas.com is operated in Malta and other jurisdictions not aforementioned, by Mr Vegas Limited, a Malta based company, with registration number C93203, and registered address at the Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Mr Vegas Limited is licensed and regulated by the Malta Gaming Authority under licence number <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" target="_blank">MGA/CRP/258/2014/01</a>, issued on the 19th of May 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/mobile/responsible-gambling/#underage-gaming"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Warning: Gambling may be addictive.",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. All rights reserved.</p>'
        ],
        'sv' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Webbplatsen mrvegas.com drivs i Sverige av Mr Vegas Limited, ett f&ouml;retag med s&auml;te p&aring; Malta med registreringsnummer C93203 och registrerad adress p&aring; The Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Mr Vegas Limited &auml;r licensierat och regleras av Spelinspektionen, under licensnummer <a href="https://www.spelinspektionen.se/sok-licens/" target="_blank">20Si2514</a>, utf&auml;rdat den 29 januari 2021 och giltigt till den 31 januari 2026 och licensnummer <a href="https://www.spelinspektionen.se/sok-licens/" target="_blank">23Si2267</a>, utf&auml;rdat den 17 januari 2024 och giltigt till den 16 januari 2029.</p>
<p id="middle" style="color: #63686c; text-align: center;">Spelande kan vara beroendeframkallande. Spela ansvarsfullt. Om du beh&ouml;ver support i f&ouml;rh&aring;llande till dina spelvanor hittar du mer information p&aring; <a href="www.stodlinjen.se" target="_blank">St&ouml;dlinjen.se.</a></p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/sv/mobile/responsible-gaming/#spel-for-vuxna"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/Spelinspektionen_logotyp.svg",
            "link": "https://www.spelinspektionen.se/"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Varning f&ouml;r spelberoende: Spelande kan vara beroendeframkallande.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Alla r&auml;ttigheter f&ouml;rbeh&aring;llna.</p>'
        ],
        'br' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Este site mrvegas.com &eacute; operado no Reino Unido pela Videoslots Limited, uma empresa sediada em Malta, com n&uacute;mero de registro C49090 e endere&ccedil;o registrado em Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, licenciada e regulamentada pela Great Britain Gambling Commission no Reino Unido sob o n&uacute;mero de licen&ccedil;a <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>.</p>
<p id="middle" style="color: #63686c; text-align: center;">O site mrvegas.com &eacute; operado em Malta e em outras jurisdi&ccedil;&otilde;es n&atilde;o mencionadas acima, pela Mr Vegas Limited, uma empresa sediada em Malta, com n&uacute;mero de registro C93203 e endere&ccedil;o registrado no Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. O Mr Vegas Limited &eacute; licenciado e regulamentado pela Malta Gaming Authority sob o n&uacute;mero de licen&ccedil;a <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" target="_blank">MGA/CRP/258/2014/01</a>, emitido em 19 de maio de 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/br/responsible-gambling/#jogo-menores"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advert&ecirc;ncia: O jogo pode ser viciante.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.gambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Todos os direitos reservados.</p>'
        ],
        'cl' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Este sitio web mrvegas.com es operado en el Reino Unido por Videoslots Limited, una empresa con sede en Malta, con n&uacute;mero de registro C49090 y domicilio social en Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, autorizada y regulada por la Great Britain Gambling Commission del Reino Unido con el n&uacute;mero de licencia <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>.</p>
<p id="middle" style="color: #63686c; text-align: center;">El sitio web mrvegas.com est&aacute; gestionado en Malta y otras jurisdicciones no mencionadas anteriormente por Mr Vegas Limited, una empresa con sede en Malta, con n&uacute;mero de registro C93203 y domicilio social en The Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Mr Vegas Limited est&aacute; autorizada y regulada por la Autoridad del Juego de Malta con el n&uacute;mero de licencia <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" target="_blank">MGA/CRP/258/2014/01</a>, expedida el 19 de mayo de 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/cl/mobile/responsible-gambling/#juegos-menores"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advertencia: El juego puede crear adicci&oacute;n.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.gambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Todos los derechos reservados.</p>'
        ],
        'da' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Denne hjemmeside mrvegas.com drives i Danmark og Gr&oslash;nland af Videoslots Limited, et Malta-baseret selskab med registreringsnummer C49090 og registreret adresse p&aring; Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, licenseret og reguleret af Spillemyndigheden under licensnummer <a href="https://www.spillemyndigheden.dk/en/licence-holder/videoslots-limited" target="_blank">18-0650512</a>, der er gyldig til og med den 29. august 2024.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_Spillemyndigheden_Emblem_RGB.png",
            "link": "https://www.spillemyndigheden.dk/en/licence-holder/videoslots-limited"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/da/mobile/responsible-gambling/#mindreariges-spil"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_centerofludomani.png",
            "link": "https://ludomani.dk/"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_StopSpillet.png",
            "link": "https://www.stopspillet.dk/"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advarsel: Gambling kan v&aelig;re vanedannende.",
            "links": [
            {"text":"StopSpillet", "link":"https://www.stopspillet.dk/"},
            {"text":"Center for Ludomani", "link":"https://ludomani.dk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Alle rettigheder forbeholdes.</p>'
        ],
        'de' => [
            'app.footer.license_detail.html' => '<p>Diese Webseite mrvegas.com wird in D&auml;nemark und Gr&ouml;nland sowie im Vereinigten K&ouml;nigreich von Videoslots Limited betrieben, einem Unternehmen mit Sitz in Malta, mit der Registrierungsnummer C49090 und der registrierten Adresse Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, lizenziert und reguliert von der Spillemyndigheden (der d&auml;nischen Gl&uuml;cksspielbeh&ouml;rde) unter der Lizenznummer<a href="https://www.spillemyndigheden.dk/en/licence-holder/videoslots-limited" rel="noopener noreferrer" target="_blank">18-0650512</a>, g&uuml;ltig bis einschlie&szlig;lich 29. August 2024, und der Great Britain Gambling Commission im Vereinigten K&ouml;nigreich unter der Lizenznummer <a href="https://beta.gamblingcommission.gov.uk/public-register/business/detail/39380" rel="noopener noreferrer" target="_blank">39380</a>. Die Webseite mrvegas.com wird in Schweden und anderen, nicht genannten Gerichtsbarkeiten von Mr Vegas Limited, einem Unternehmen mit Sitz in Malta, mit der Registrierungsnummer C93203 und der registrierten Adresse im Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta betrieben. Mr Vegas Limited ist lizenziert und reguliert durch die Spelinspektionen (die schwedische Gl&uuml;cksspielbeh&ouml;rde), unter der Lizenznummer <a href="https://www.spelinspektionen.se/" rel="noopener noreferrer" target="_blank">20Si2514</a>, ausgestellt am 29. Januar 2021 und g&uuml;ltig bis zum 31. Januar 2026, und durch die Malta Gaming Authority unter der Lizenznummer <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" rel="noopener noreferrer" target="_blank">MGA/CRP/258/2014/01</a>, ausgestellt am 19. Mai 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com//file_uploads/gamblingcommission_logo.png",
            "link": "https://registers.gamblingcommission.gov.uk/39380"
            },
            {
            "image": "https://www.mrvegas.com//file_uploads/spelinspektionen_logo.png",
            "link": "https://www.spelinspektionen.se/"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/18plus_logo.png",
            "link": "https://www.mrvegas.com/de/responsible-gambling/#underage-gaming"
            }                        
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Warnung: Gl&uuml;cksspiel kann s&uuml;chtig machen.",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p class="MsoNormal">&copy; Mrvegas.com 2020 - 2024. Alle Rechte vorbehalten.</p>'
        ],
        'es' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Este sitio web mrvegas.com es operado en el Reino Unido por Videoslots Limited, una empresa con sede en Malta, con n&uacute;mero de registro C49090 y domicilio social en Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, autorizada y regulada por la Great Britain Gambling Commission del Reino Unido con el n&uacute;mero de licencia <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>.</p>
<p id="middle" style="color: #63686c; text-align: center;">El sitio web mrvegas.com est&aacute; gestionado en Malta y otras jurisdicciones no mencionadas anteriormente por Mr Vegas Limited, una empresa con sede en Malta, con n&uacute;mero de registro C93203 y domicilio social en The Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Mr Vegas Limited est&aacute; autorizada y regulada por la Autoridad del Juego de Malta con el n&uacute;mero de licencia <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" target="_blank">MGA/CRP/258/2014/01</a>, expedida el 19 de mayo de 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/es/mobile/responsible-gaming/#juegos-menores"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advertencia: El juego puede crear adicci&oacute;n.",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Todos los derechos reservados.</p>'
        ],
        'fi' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">T&auml;t&auml; verkkosivustoa mrvegas.com yll&auml;pit&auml;&auml; Yhdistyneess&auml; kuningaskunnassa Videoslots Limited, Maltalla sijaitseva yritys, jonka rekisterinumero on C49090 ja rekister&ouml;ity osoite Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, ja joka on Ison-Britannian uhkapelikomission lisensoima ja s&auml;&auml;ntelem&auml; Yhdistyneess&auml; kuningaskunnassa lisenssinumerolla <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>.</p>
<p id="middle" style="color: #63686c; text-align: center;">Verkkosivustoa mrvegas.com hallinnoi Maltalla ja muilla kuin edell&auml; mainituilla laink&auml;ytt&ouml;alueilla Mr Vegas Limited, joka on Maltalla sijaitseva yritys, jonka rekisterinumero on C93203 ja rekister&ouml;ity osoite Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Mr Vegas Limitedill&auml; on Maltan peliviranomaisen my&ouml;nt&auml;m&auml; ja s&auml;&auml;ntelem&auml; lisenssi, jonka numero on <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" target="_blank">MGA/CRP/258/2014/01</a> ja joka on my&ouml;nnetty 19. toukokuuta 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/fi/mobile/responsible-gaming/#alaikaisten-pelaamisen-estaminen"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Varoitus: Rahapelaaminen voi aiheuttaa riippuvuutta.",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Kaikki oikeudet pid&auml;tet&auml;&auml;n.</p>'
        ],
        'hi' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">mrvegas.com वीडियोलॉट्स लिमिटेड द्वारा ग्रीनलैंड और डेनमार्क, यूके संचालित है। यह एक माल्टा कंपनी है और पता स्पेस, लेवल 2 और 3, अल्फ्रेड क्रेग स्ट्रीट, पिएटा, पीटीए 1320 माल्टा है। इसकी पंजीकरण संख्या C49090 है, जिसे <a href="https://www.spillemyndigheden.dk/en/licence-holder/videoslots-limitedrel=" rel="noopener noreferrer" target="_blank">18-0650512</a> नंबर के साथ स्पिलेमाइंडिघेडेन (डेनिश जुआ प्राधिकरण) द्वारा विनियमित और लाइसेंस दिया गया है। यह 29 अगस्त 2024 तक वैध है, यूके में यूके जुआ आयोग <a href="https://beta.gamblingcommission.gov.uk/public-register/business/detail/39380rel=" rel="noopener noreferrer" target="_blank">39380</a> नंबर के साथ पंजीकृत है। mrvegas.com वेबसाइट स्वीडन में संचालित है और कुछ अन्य अधिकार क्षेत्र मिस्टर वेगास लिमिटेड द्वारा पूर्वोक्त नहीं हैं। यह एक माल्टा कंपनी भी है जो स्पेस, लेवल 2 और 3, अल्फ्रेड क्रेग स्ट्रीट, पिएटा, पीटीए 1320 माल्टा में पंजीकरण संख्या C93203 के साथ स्थित है। Mr.vegas ltd 29 जनवरी 2021 को जारी स्पेलिन्सपेक्शनन (स्वीडिश जुआ प्राधिकरण) द्वारा विनियमित और लाइसेंस प्राप्त है और 31 जनवरी 2026, लाइसेंस संख्या <a href="https://www.spelinspektionen.se/rel=" rel="noopener noreferrer" target="_blank">20Si2514</a> तक वैध है। माल्टा गेमिंग प्राधिकरण द्वारा लाइसेंस संख्या <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" rel="noopener noreferrer" target="_blank">MGA/CRP/258/2014/01</a> के साथ, यह 19 मई 2020 को जारी किया जाता है।</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://registers.gamblingcommission.gov.uk/39380"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/spelinspektionen_logo.png",
            "link": "https://www.spelinspektionen.se/"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/spillemyndigheden_logo.png",
            "link": "https://www.spillemyndigheden.dk/en/licence-holder/videoslots-ltd/"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.mrvegas.com/hi/responsible-gambling/#underage-gaming"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "चेतावनी: जुआ व्यसनी हो सकता है",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}            
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Mrvegas.com 2020 - 2024. All rights reserved.</p>'
        ],
        'it' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Questo sito web mrvegas.com &egrave; gestito nel Regno Unito da Videoslots Limited, una societ&agrave; con sede a Malta, con numero di registrazione C49090 e indirizzo registrato presso Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, autorizzata e regolata dalla Great Britain Gambling Commission nel Regno Unito con il numero di licenza <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>.</p>
<p id="middle" style="color: #63686c; text-align: center;">Il sito web mrvegas.com &egrave; gestito a Malta e in altre giurisdizioni non menzionate sopra, da Mr Vegas Limited, una societ&agrave; con sede a Malta, con numero di registrazione C93203 e indirizzo registrato presso lo Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Mr Vegas Limited &egrave; autorizzata e regolamentata dalla Malta Gaming Authority con licenza numero <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" target="_blank">MGA/CRP/258/2014/01</a>, rilasciata il 19 maggio 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/it/mobile/responsible-gaming/#gioco-protetto-sicuro"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/adm_logo.png",
            "link": "https://www.adm.gov.it/portale/"
            }
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Attenzione: Il gioco d\'azzardo pu&ograve; creare dipendenza.",
            "links": [
            {"text":"BeGambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Tutti i diritti riservati.</p>'
        ],
        'no' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Dette nettstedet mrvegas.com drives i Storbritannia av Videoslots Limited, et Malta-basert selskap, med registreringsnummer C49090, og registrert adresse p&aring; Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, lisensiert og regulert av Great Britain Gambling Commission i Storbritannia under lisensnummer <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>.</p>
<p id="middle" style="color: #63686c; text-align: center;">Nettstedet mrvegas.com drives p&aring; Malta og andre jurisdiksjoner som ikke er nevnt ovenfor, av Mr Vegas Limited, et Malta-basert selskap, med registreringsnummer C93203, og registrert adresse p&aring; Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Mr Vegas Limited er lisensiert og regulert av Malta Gaming Authority under lisensnummer <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" target="_blank">MGA/CRP/258/2014/01</a>, utstedt den 19. mai 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/responsible-gambling/#underage-gaming"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advarsel: Pengespill kan v&aelig;re avhengighetsskapende.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Alle rettigheter forbeholdt.</p>'
        ],
        'on' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Videoslots Limited is a registered agent of iGaming Ontario as of 1st June 2023, licensed and regulated by the Alcohol and Gaming Commission of Ontario under registration number <a href="https://www.iagco.agco.ca/prod/pub/en/Default.aspx?PossePresentation=Default&PosseObjectId=137793426" target="_blank">OPIG1273101</a>, having its registered address at The Space, Level 2&3, Triq Alfred Craig, Pieta, Malta PTA1320.</p>
<p id="middle" style="color: #63686c; text-align: center;">This website is for the use of adults 19 years of age or older in the Province of Ontario.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer-over-19s-only-c.png",
            "link": "https://www.mrvegas.com/responsible-gambling/#preventing-underage-gambling"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/igamingontariologotransbg.png",
            "link": "https://igamingontario.ca/en"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/connexontariologotransbg.png",
            "link": "https://www.connexontario.ca/en-ca/"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Gambling can be addictive. Play responsibly. 19+ <br/>Please contact",
            "links": [
            {"text":"ConnexOntario", "link":"https://www.connexontario.ca/en-ca/"}            
            ],
            "phone": "at 1-866-531-2600 to speak to an advisor, free of charge."
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; Mrvegas.com 2020 - 2024. All rights reserved.</p>'
        ],
        'pe' => [
            'app.footer.license_detail.html' => '<p id="middle" style="color: #63686c; text-align: center;">Este sitio web mrvegas.com es operado en el Reino Unido por Videoslots Limited, una empresa con sede en Malta, con n&uacute;mero de registro C49090 y domicilio social en Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta, autorizada y regulada por la Great Britain Gambling Commission del Reino Unido con el n&uacute;mero de licencia <a href="https://www.gamblingcommission.gov.uk/public-register/business/detail/39380" target="_blank">39380</a>.</p>
<p id="middle" style="color: #63686c; text-align: center;">El sitio web mrvegas.com est&aacute; gestionado en Malta y otras jurisdicciones no mencionadas anteriormente por Mr Vegas Limited, una empresa con sede en Malta, con n&uacute;mero de registro C93203 y domicilio social en The Space, Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Malta. Mr Vegas Limited est&aacute; autorizada y regulada por la Autoridad del Juego de Malta con el n&uacute;mero de licencia <a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" target="_blank">MGA/CRP/258/2014/01</a>, expedida el 19 de mayo de 2020.</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/pe/mobile/responsible-gaming/#juegos-menores"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://www.gamblingcommission.gov.uk/public-register/business/detail/39380"
            }         
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "Advertencia: El juego puede crear adicci&oacute;n.",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}            
            ]            
            }',
            'app.footer.copyright.html' => '<p id="middle" style="color: #63686c; text-align: center;">&copy; MrVegas.com 2020 - 2024. Todos los derechos reservados.</p>'
        ],
        'ja' => [
            'app.footer.license_detail.html' => '<p>当社ウェブサイトmrvegas.comは、マルタで設立されたVideoslots Limited（登録番号C49090）によってデンマーク、グリーンランドおよびイギリスで運営されています。Videoslots Ltd.は、The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Maltaに登記上の本社を置いています。Videoslots Ltdは、デンマークのSpillemyndighedenによって、2024年8月29日まで有効なライセンス番号<a href="https://www.spillemyndigheden.dk/en/licence-holder/videoslots-limited" rel="noopener noreferrer" target="_blank">18-0650512</a>の下、ライセンス認証および統制されています。またイギリスのGambling Commission（以下、GCGB）によっても、ライセンス番号<a href="https://beta.gamblingcommission.gov.uk/public-register/business/detail/39380" rel="noopener noreferrer" target="_blank">39380</a>の下、ライセンス認証および統制されています. 当社ウェブサイトmrvegas.com は、マルタで設立されたMr Vegas Limited（登録番号 C93203）によってスウェーデン、その他上記外区域で運営されています。 Mr Vegas Limited.は、The Space Level 2 & 3, Alfred Craig Street, Pieta, PTA 1320 Maltaに登記上の本社を置いています。Mr Vegas Ltd.は、スウェーデンのSpelinspektionenによって、2021年1月29日発行ならびに2026年1月31日まで有効なライセンス番号<a href="https://www.spelinspektionen.se/" rel="noopener noreferrer" target="_blank">20Si2514</a>の下、ライセンス認証および統制されています。またマルタ共和国のMalta Gaming Authorityによっても、2020年5月20日に発行されたライセンス番号<a href="https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1" rel="noopener noreferrer" target="_blank">MGA/CRP/258/2014</a>の下、ライセンス認証および統制されています</p>',
            'app.footer.license_logo.json' => '[
            {
            "image": "https://www.videoslots.com/file_uploads/mga_logo.png",
            "link": "https://authorisation.mga.org.mt/verification.aspx?lang=EN&company=bb3ea3e9-407c-47f1-8096-451f5ddf859d&details=1"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/gamblingcommission_logo.png",
            "link": "https://registers.gamblingcommission.gov.uk/39380"
            },
            {
            "image": "https://www.videoslots.com/file_uploads/spelinspektionen_logo.png",
            "link": "https://www.spelinspektionen.se/"
            },            
            {
            "image": "https://www.videoslots.com/file_uploads/18plus_logo.png",
            "link": "https://www.mrvegas.com/responsible-gambling/#underage-gaming"
            }            
            ]',
            'app.footer.responsible-gambling.json' => '{
            "title": "警告：ギャンブルは中毒性があるかもしれません",
            "links": [
            {"text":"Be GambleAware", "link":"https://www.begambleaware.org/"},
            {"text":"Gamblers Anonymous", "link":"https://www.gamblersanonymous.org.uk/"}                  
            ]
            }',
            'app.footer.copyright.html' => '<p class="MsoNormal">&copy; mrvegas.com 2023. All rights reserved.</p>'
        ],
    ];

    private array $KS_data = [
        'en' => [
            'app.footer.license_detail.html' => '',
            'app.footer.license_logo.json' => '',
            'app.footer.responsible-gambling.json' => '',
            'app.footer.copyright.html' => ''
        ],
    ];

    private array $MR_data = [
        'en' => [
            'app.footer.license_detail.html' => '',
            'app.footer.license_logo.json' => '',
            'app.footer.responsible-gambling.json' => '',
            'app.footer.copyright.html' => ''
        ],
    ];

    public function init()
    {
        parent::init();
        if (getenv('APP_SHORT_NAME') === 'VS') {
            $this->data = $this->VS_data;
        } else if (getenv('APP_SHORT_NAME') === 'MV') {
            $this->data = $this->MV_data;
        } else if (getenv('APP_SHORT_NAME') === 'KS') {
            $this->data = $this->KS_data;
        } else if (getenv('APP_SHORT_NAME') === 'MR') {
            $this->data = $this->MR_data;
        }
    }
}