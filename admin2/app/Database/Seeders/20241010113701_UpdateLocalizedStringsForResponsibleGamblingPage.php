<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsForResponsibleGamblingPage extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'simple.644.html',
            'value' => '<h1>Responsible Gaming</h1>
                        <p style="text-align: justify;">Dbet.com (DBET) sees online casino games as a positive form of entertainment for adults. Most players share this opinion, but there is a small percentage that lets gambling become a too big part of their lives. If you feel that your gambling is a problem, dbet.com can help you to gamble responsibly. We can adjust how much money you can deposit, lose or wager according to your request. You also have the option to take a break or self-exclude should you require it. <a href="/customer-service">Contact us</a> and we will take necessary action that fits you the best.</p>
                        <ol class="page-sections_link_box">
                        <li><strong><a href="#support-groups">Support Groups</a></strong></li>
                        <li><strong><a href="#underage-gaming">Underage Gaming</a></strong></li>
                        <li><strong><a href="#player-self-exclusion">Player Self-Exclusion</a></strong></li>
                        <li><strong><a href="#maintain-control">Maintain Control</a></strong></li>
                        <li><strong><a href="#gamstop">GAMSTOP</a></strong></li>
                        <li><strong><a href="#software">Block access to online gambling</a></strong></li>
                        </ol>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="support-groups" style="text-align: justify;">Support Groups</h2>
                        <p style="text-align: justify;">If you feel that you need to talk to a professional about your gambling or you feel that you need counselling, contact one of these independent organisations for help:&nbsp;</p>
                        <h3 style="text-align: justify;">GamCare</h3>
                        <p style="text-align: justify;"><strong>Web:</strong> <a href="http://www.gamcare.org.uk" rel="noopener noreferrer" target="_blank">www.gamcare.org.uk</a> <br><strong>E-mail:</strong> <a href="mailto:info@gamcare.org.uk" rel="noopener noreferrer" target="_blank">info@gamcare.org.uk</a> <br><strong>Helpline:</strong> (+44) 0808 8020 133<br><strong>Address:</strong> 2 &amp; 3 Baden Place, Crosby Row, London, SE1 1YW</p>
                        <h3 style="text-align: justify;">GambleAware</h3>
                        <p style="text-align: justify;"><strong>Web:</strong>&nbsp;<a href="https://www.begambleaware.org/" rel="noopener noreferrer" target="_blank">www.begambleaware.org</a><br><strong>Helpline:</strong> (+44) 0808 8020 133&nbsp;</p>
                        <h3 style="text-align: justify;">Gordonhouse</h3>
                        <p style="text-align: justify;"><strong>Web:</strong> <a href="http://www.gamblingtherapy.org" rel="noopener noreferrer" target="_blank">www.gamblingtherapy.org</a> <br><strong>E-mail:</strong> <a href="mailto:webmaster@gamblingtherapy.org" rel="noopener noreferrer" target="_blank">webmaster@gamblingtherapy.org</a> <br><strong>Live support: <a href="https://www.gamblingtherapy.org/talk-to-us/" rel="noopener noreferrer" target="_blank">[Here]</a></strong><br><strong>Address:</strong> The Gambling Therapy pilot, Gordon House Association, 114 Wellington Road, Dudley, West Midlands, DY1 1UB, UK</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="underage-gaming" style="text-align: justify;">Underage Gaming</h2>
                        <p style="text-align: justify;">Playing at dbet.com is restricted to persons over the age of 18, or the legal age of majority in their jurisdiction, whichever is greater. <br><br>Any details we request from you are used to verify your identity - this is both for your own protection and ours. If you have any questions, please do not hesitate to <a href="/customer-service/contact-us" target="_self">contact us</a> at any time.</p>
                        <h3 style="text-align: justify;">Protection of Minors</h3>
                        <p style="text-align: justify;">Underage gambling is illegal if you are younger than 18 years of age, or any higher minimum age as required by the law of the jurisdiction applicable to you. The company does not entertain players that are not of legal age and does not pay out wins to such players.</p>
                        <p style="text-align: justify;">With the Internet so readily accessible in homes around the world, responsible online gaming relies heavily on responsible parenting. <br><br>In order to ensure child safety on the Internet, Kungaslottet encourages its players to make use of filtering software to prevent minors from accessing certain online material.</p>
                        <p style="text-align: justify;"><a href="http://www.gamcare.org.uk/get-advice/what-can-you-do/blocking-software#.VmbUbPmrSM9" rel="noopener noreferrer" target="_blank">Gamecare</a><br><a href="https://www.netnanny.com/features/internet-filter/" rel="noopener noreferrer" target="_blank">Netnanny</a></p>
                        <h4 style="text-align: justify;"><strong>Tips for Parents:</strong></h4>
                        <ul style="text-align: justify;">
                        <li>Do not leave your computer unattended when your casino software is running.</li>
                        <li>Password-protect your casino program.</li>
                        <li>Do not allow persons under 18 to participate in any gambling activity.</li>
                        <li>Keep your casino account number and payment card(s) out of reach of children.</li>
                        <li>Do not save passwords onto your computer. Write them down instead.</li>
                        <li>Limit the amount of time your children spend online and make use of software to prevent your children from accessing inappropriate material.</li>
                        </ul>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="player-self-exclusion" style="text-align: justify;">Player Self-Exclusion</h2>
                        <p style="text-align: justify;">Kungaslottet recognises that whilst most players gamble for entertainment, gambling can be harmful to a small number of people. We are committed to creating a responsible gaming environment.&nbsp;</p>
                        <p style="text-align: justify;">If you are concerned about your gambling behaviour, please consider entering a self-exclusion.</p>
                        <p style="text-align: justify;">Kungaslottet is committed to giving our players an enjoyable and safe gaming experience. If you choose to enter a self-exclusion, your dbet.com account will be self-excluded immediately for the chosen period. This means that you will not be able to login, deposit or play at dbet.com with whilst self-excluded. Please note that you will not be eligible to re-open your account until the chosen self-exclusion period has expired.</p>
                        <p style="text-align: justify;">We will also take all reasonable steps to ensure you do not receive any promotional material during this time. Nevertheless, if you use Social Media channels, we strongly recommend you take steps to ensure you don’t receive our news or updates.</p>
                        <p style="text-align: justify;">You have the option to self-exclude from 6 months up to five years.&nbsp;</p>
                        <p style="text-align: justify;">We also recommend that you self-exclude with any other operator you may be registered with.</p>
                        <p style="text-align: justify;">To request a self-exclusion, please contact our support team through <a href="/customer-service/contact-us" target="_self">email or chat</a>. Or you can enter a self-exclusion from your account "My Profile" -&gt; "Responsible Gaming" whilst logged in to your dbet.com account.</p>
                        <p style="text-align: justify;">&nbsp;</p>
                        <h2 style="text-align: justify;">UK Player Self-Exclusion</h2>
                        <p style="text-align: justify;">Any self-exclusion may, upon request, be extended for one or more further periods of at least 6 months. In order to extend an existing self-exclusion, please contact our customer support team through email or chat.</p>
                        <p style="text-align: justify;">Upon re-opening request (Live chat, e-mail) when the chosen period of time has expired, one of our CS representatives will contact you over the phone in order to provide you with self-exclusion implications and education. We will then implement a one-day cooling-off period so you can reconsider the decision to access gambling again.&nbsp;</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="maintain-control" style="text-align: justify;">Maintain Control</h2>
                        <p style="text-align: justify;">We recognise that whilst the majority of players gamble for entertainment, there is a small number who may lose control of their gambling. Kungaslottet fully support responsible gaming and to ensure that you continue to enjoy a safe and manageable play, please bear the following in mind:</p>
                        <ul>
                        <li>Gambling should be seen as entertainment and not as a means to make money</li>
                        <li>Keep track of the time and money you spend while gambling.</li>
                        <li>Use limits to help stay in control</li>
                        </ul>
                        <p style="text-align: justify;">The following list is a variety of measures and tools to help you stay in control of the time and money you spend on dbet.com:</p>
                        <ul>
                        <li>Wager limit to control how much you bet.</li>
                        <li>Deposit limit to limit how much you can deposit into your account on dbet.com.</li>
                        <li>Loss limit helps you to not overspend from your budget.</li>
                        <li>Max bet limit to protect yourself from accidently place higher spin or bet than intended.</li>
                        <li>Time-out limit to control that you don’t spend more time than intended per game session.</li>
                        <li>Reality check to receive information about your net winnings in your current session.</li>
                        <li>Lock account is a tool to use if you need a short break from your gambling.</li>
                        </ul>
                        <p style="text-align: justify;">Before you start playing, we recommend you to take a look at, and make use of our limits. For more information about these tools and how to set them please login to your account and go to "My Profile" -&gt; "Responsible Gaming".</p>
                        <p style="text-align: justify;">Should you need a break from gambling, self-exclusion can be set from within the ‘My Account’ section whilst logged in or by contacting our customer support department through <a href="/customer-service/contact-us" target="_self">email or chat</a>.&nbsp; &nbsp;</p>
                        <p style="text-align: justify;">If you need to talk to someone about any concerns you may have with your gambling, please contact one of the organisations, which you can find under <a href="#support-groups">Support Groups</a>.&nbsp; &nbsp;</p>
                        <h2 style="text-align: justify;">UK Player</h2>
                        <p style="text-align: justify;">After your registration and before you start playing, dbet.com will ask you to specify your occupation and the amount you can afford to spend on gambling per month. Please consider that the above amount will be set as your monthly limit of loss and will be used for KYC and responsible gambling purposes. When the limit has been reached you will get a message which informs you that your loss limit has been reached and you will not be able to place any further bets.</p>
                        <p style="text-align: justify;">You will be able to review these settings should your occupation and amount change. You can change or remove your loss limit. The change will automatically take place after a period of 7 days. If you want to decrease your limit, the change will take place with immediate effect.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="gamstop" style="text-align: justify;">GAMSTOP</h2>
                        <p style="text-align: justify;">GAMSTOP lets you set controls to help restrict your online gambling activities.</p>
                        <p style="text-align: justify;">Sign up for the service and you can choose to be barred from using gambling websites and apps run by companies licensed in Great Britain, for a period of time of your choice. Sign up <a href="https://www.gamstop.co.uk/personal-details/" rel="noopener noreferrer" target="_blank">here</a>.</p>
                        <p style="text-align: justify;">After you sign up, you will receive an email summarising all the details of your exclusion from gambling activities. It can take up to 24 hours for your self-exclusion to become effective.</p>
                        <p style="text-align: justify;">After this period, you will be excluded from gambling with any online gambling companies licensed in Great Britain.</p>
                        <p style="text-align: justify;">Depending on the option you choose, you will self-exclude yourself for a period of 6 months, 1 year or 5 years. Once the minimum duration period has elapsed you can come back to GAMSTOP&nbsp;to ask for the self-exclusion to be lifted, after which you will go through the relevant process.&nbsp;</p>
                        <p style="text-align: justify;">You can find more detailed information about GAMSTOP&nbsp;<a href="https://www.gamstop.co.uk/" rel="noopener noreferrer" target="_blank">here</a>, on their website.&nbsp;</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="software" style="text-align: justify;">Block access to online gambling websites</h2>
                        <p style="text-align: justify;">If you want to block access to online gambling sites from your computer, we recommend you to use a software from GamBlock. Please read more at <a href="http://www.gamblock.com/index.html" rel="noopener noreferrer" target="_blank">www.gamblock.com</a>.</p>
                        <p style="text-align: justify;">You can also block access to gambling sites by using <a href="https://www.betblocker.org" rel="noopener noreferrer" target="_blank">Betblocker.org</a>.</p>'
        ],
        [
            'language' => 'br',
            'alias' => 'simple.644.html',
            'value' => '<h1>Jogo Responsável</h1>
                        <p style="text-align: justify;">A dbet.com vê os jogos de cassino online como uma forma de entretenimento positiva para os adultos. A maioria dos jogadores compartilha dessa opinião, mas existe um pequena porcentagem que permite que os jogos de azar se torme uma parte muito grande de suas vidas. Se você sentir que os seus jogos de azar são um problema, a dbet.com pode ajudá-lo(a) a apostar com responsabilidade. Nós ajustamos o quanto de dinheiro você pode depositar, perder ou apostar de acordo com a sua solicitação. Você também tem a opção de dar um tempo ou se auto excluir caso seja necessário.&nbsp;&nbsp;<a href="/customer-service">Entre em contato</a> e nós tomaremos as ações necessárias mais adequadas para você. </p>
                        <ol class="page-sections_link_box">
                        <li><strong><a href="#support-groups"> Grupos de Suporte</a></strong></li>
                        <li><strong><a href="#jogo-menores">Jogo para Menores de Idade</a></strong></li>
                        <li><strong><a href="#player-self-exclusion">Auto-exclusão de Jogador</a></strong></li>
                        <li><strong><a href="#maintain-control">Manter o Controle</a></strong></li>
                        <li><strong><a href="#gamstop">GAMSTOP</a></strong></li>
                        <li><strong><a href="#software">Bloquear acesso para jogos de azar online</a></strong></li>
                        </ol>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="support-groups" style="text-align: justify;">Grupos de Suporte</h2>
                        <p style="text-align: justify;">Se você sente que precisa conversar com um profissional sobre os seus jogos de azar ou precisa de conselho, entre em contato com uma dessas organizações independentes de ajuda:&nbsp;</p>
                        <h3 style="text-align: justify;">GamCare</h3>
                        <p style="text-align: justify;"><strong>Website:</strong> <a href="http://www.gamcare.org.uk" rel="noopener noreferrer" target="_blank">www.gamcare.org.uk</a> <br><strong>E-mail:</strong> <a href="mailto:info@gamcare.org.uk" rel="noopener noreferrer" target="_blank">info@gamcare.org.uk</a> <br><strong>Telefone:</strong> (+44) 0808 8020 133<br><strong>Endereço:</strong> 2 &amp; 3 Baden Place, Crosby Row, London, SE1 1YW</p>
                        <h3 style="text-align: justify;">GambleAware</h3>
                        <p style="text-align: justify;"><strong>Website:</strong>&nbsp;<a href="https://www.begambleaware.org/" rel="noopener noreferrer" target="_blank">www.begambleaware.org</a><br><strong>Telefone:</strong> (+44) 0808 8020 133&nbsp;</p>
                        <h3 style="text-align: justify;">Gordonhouse</h3>
                        <p style="text-align: justify;"><strong>Website:</strong> <a href="http://www.gamblingtherapy.org" rel="noopener noreferrer" target="_blank">www.gamblingtherapy.org</a> <br><strong>E-mail:</strong> <a href="mailto:webmaster@gamblingtherapy.org" rel="noopener noreferrer" target="_blank">webmaster@gamblingtherapy.org</a> <br><strong>Suporte ao vivo: <a href="https://v2.zopim.com/widget/livechat.html?api_calls=%5B%5D&amp;hostname=www.gamblingtherapy.org&amp;key=3rmcboQG3XbDlIIe3WFgsE4tyIa4jui6&amp;lang=en&amp;%20" rel="noopener noreferrer" target="_blank">[Aqui]</a></strong><br><strong>Endereço:</strong> The Gambling Therapy pilot, Gordon House Association, 114 Wellington Road, Dudley, West Midlands, DY1 1UB, UK</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="jogo-menores" style="text-align: justify;">Jogo para Menores de Idade</h2>
                        <p style="text-align: justify;">Jogar no dbet.com é restrito a pessoas maiores de 18 anos de idade ou a maioridade legal em suas jurisdições. <br><br>Qualquer detalhe que solicitarmos é utilizado para verificar a sua identidade - isso é para a sua própria proteção e a nossa, respectivamente. Se você tem alguma dúvida, por favor, não hesite em <a href="/customer-service/contact-us" target="_self">entrar em contato conosco</a> a qualquer momento.</p>
                        <h3 style="text-align: justify;"> Proteção de Menores</h3>
                        <p style="text-align: justify;">Jogos de azar para menores de idade são ilegais, ou seja, se você tem menos de 18 anos de idade ou qualquer idade mínima conforme exigido pela lei da jurisdição que se aplica a você. A empresa não aceita jogadores que não possuem a idade legal e não paga os ganhos para eles.</p>
                        <p style="text-align: justify;">Com a internet tão acessível nas casas em todo o mundo, o jogo online responsável depende fortemente dos pais e/ou responsáveis. <br><br>Para garantir a segurança das crianças na internet, a
                            Kungaslottet incentiva os seus jogadores a fazerem uso de softwares de filtragem, e assim prevenir que os menores tenham acesso a certo tipo de material online.</p>
                        <p style="text-align: justify;"><a href="http://www.gamcare.org.uk/get-advice/what-can-you-do/blocking-software#.VmbUbPmrSM9" rel="noopener noreferrer" target="_blank">Gamecare</a><br><a href="https://www.netnanny.com/features/internet-filter/" rel="noopener noreferrer" target="_blank">Netnanny</a></p>
                        <h4 style="text-align: justify;"><strong>Dicas para os Pais:</strong></h4>
                        <ul style="text-align: justify;">
                        <li>Não deixe o seu computador sem supervisão enquanto o seu software de cassino estiver funcionando.</li>
                        <li>Proteja o seu programa de cassino com uma senha.</li>
                        <li>Não permita que pessoas menores de 18 anos participem de nenhuma atividade de jogos de azar.</li>
                        <li>Mantenha o número da sua conta de cassino e do seu cartão de crédito fora do alcance das crianças.</li>
                        <li>Não salve senhas em seu computador. Aos invés disso, escreva em algum lugar.</li>
                        <li>Limite o tempo que os seus filhos passam online e faça uso de softwares que previnem que eles acessem materiais inapropriados.</li>
                        </ul>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="player-self-exclusion" style="text-align: justify;">Auto-exclusão de Jogador&nbsp;&nbsp;</h2>
                        <p style="text-align: justify;">O dbet.com reconhece que muitos jogam para se divertir, mas jogos de azar podem ser prejudiciais para um pequeno número de pessoas. Por isso, estamos empenhados em criar um ambiente de jogo responsável.&nbsp;</p>
                        <p style="text-align: justify;">Se você está preocupado com o seu comportamento de jogo, por favor, considere a sua auto-exclusão.</p>
                        <p style="text-align: justify;">O dbet.com está empenhado em oferecer aos seus jogadores uma experiência de jogo agradável e segura. Se você escolheu fazer uma auto-exclusão, a sua conta no dbet.com será excluída imediatamente no período escolhido. Isso significa que você não poderá logar, depositar ou jogar no dbet.com enquanto estiver auto excluído. Por favor, observe que você não estará elegível para reabrir sua conta até que o período de auto-exclusão expire.</p>
                        <p style="text-align: justify;">Nós também tomamos todos os procedimentos razoáveis para garantir que você não receba nenhum material promocional durante este período. No entanto, se você utiliza algum canal de Mídia Social, nós recomendamos fortemente que tome medidas para garantir que não receba nossas notícias ou atualizações.</p>
                        <p style="text-align: justify;">Você tem a opção de se auto excluir entre seis meses e cinco anos.&nbsp;</p>
                        <p style="text-align: justify;">Também recomendamos que você se auto exclua em qualquer outro operador que tenha se registrado.</p>
                        <p style="text-align: justify;">Para solicitar a auto-exclusão, por favor, entre em contato com a nossa equipe de suporte através do <a href="/customer-service/contact-us" target="_self">e-mail ou chat</a>. Você pode se auto excluir através da sua conta, clicando em "Meu Perfil" - "Jogo Responsável" enquanto estiver conectado no dbet.com.</p>
                        <p style="text-align: justify;"><br></p>
                        <h2 style="text-align: justify;">Auto-exclusão para Jogadores do Reino Unido</h2>
                        <p style="text-align: justify;">Qualquer Auto-exclusão pode, mediante solicitação, ser estendida por um ou mais períodos de pelo menos seis meses. Para estender uma Auto-exclusão, por favor, entre em contato com a nossa equipe de suporte por e-mail ou chat.</p>
                        <p style="text-align: justify;">Mediante uma solicitação de reabertura de conta (Chat ao vivo, e-mail) que o período escolhido expirou, um dos nossos representantes de atendimento ao cliente entrará em contato por telefone para fornecer informações educacionais e as implicações da auto-exclusão. Depois disso, nós implementaremos um período de reflexão de um dia para você reconsiderar a decisão de ter acesso a jogos de azar novamente.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="maintain-control" style="text-align: justify;">Manter o controle&nbsp;&nbsp;</h2>
                        <p style="text-align: justify;">Nós reconhecemos que enquanto a maioria dos jogadores apostam por diversão, existe um número pequeno que perde o controle dos seus jogos de azar. O dbet.com apoia totalmente o jogo responsável. Para garantir que você continue a aproveitar um jogo seguro e administrável, por favor, tenha em mente o seguinte:</p>
                        <ul>
                        <li>Jogos de azar devem ser vistos como entretenimento e não como um meio de ganhar dinheiro</li>
                        <li>Preste atenção no tempo e no dinheiro que você gasta enquanto joga.</li>
                        <li>Utilize limites para ajudá-lo a estar no controle</li>
                        </ul>
                        <p style="text-align: justify;">A lista a seguir contém uma variedade de medidas e ferramentas para ajudá-lo a manter o controle do tempo e do dinheiro que você gasta no dbet.com&nbsp;</p>
                        <ul>
                        <li>Limite de apostas para controlar o quanto você pode apostar.</li>
                        <li>Limite de depósito para controlar o quanto você pode depositar na sua conta no dbet.com.</li>
                        <li>Limite de perdas para ajudá-lo a não gastar mais do que o seu orçamento.</li>
                        <li>Limite máximo de apostas para protegê-lo(a) de acidentalmente apostar ou participar de rodadas mais elevadas do que está intencionado(a).</li>
                        <li>Limite de tempo para controlar que você não gaste mais tempo do que devia em uma sessão de jogo.</li>
                        <li>Verificação de realidade para receber informações sobre os seus ganhos líquidos na sua sessão atual.</li>
                        <li>Bloquear conta é uma ferramenta que pode ser usada se você precisa dar um tempo com os seus jogos de azar.</li>
                        </ul>
                        <p style="text-align: justify;">Antes de começar a jogar, recomendamos que dê uma olhada e tenha certeza dos nossos limites. Para mais informações sobre estas ferramentas e como configurá-las, por favor, entre na sua conta e vá até "Meu Perfil" - "Jogo Responsável".</p>
                        <p style="text-align: justify;">Se você precisa dar um tempo dos jogos de azar, a auto-exclusão pode ser configurada dentra da seção Minha Conta enquanto estiver logado ou entrando em contato com o departamento de suporte ao cliente por meio do <a href="/customer-service/contact-us" target="_self">e-mail ou chat</a>.&nbsp; &nbsp;</p>
                        <p style="text-align: justify;">Se você precisa falar com alguém sobre qualquer preocupação que tenha com o seu jogo de azar, por favor, entre em contato com uma das organizações que você pode encontrar em <a href="#support-groups">Grupos de Suporte</a>.&nbsp; &nbsp;</p>
                        <h2 style="text-align: justify;">Jogadores do Reino Unido</h2>
                        <p style="text-align: justify;">Depois do seu registro e antes de começar a jogar, o dbet.com solicitará que especifique a sua ocupação e o total que pode disponibilizar por mês para gastar em jogos de azar. Por favor, considere que o valor acima será configurado como o seu limite mensal de perdas. Ele será usado pelo KYC e para fins de jogo responsável. Quando o limite for alcançado, você receberá uma mensagem informando que o seu limite de perdas foi alcançado e você não pode participar de apostas futuras.</p>
                        <p style="text-align: justify;">Você poderá revisar essas configurações caso a sua ocupação e valores mudem. Você pode mudar ou remover o seu limite de perdas. A mudança será concretizada automaticamente depois de sete dias. Se você deseja diminuir o seu limite, o mudança será efetivada de imediato.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="gamstop" style="text-align: justify;">GAMSTOP</h2>
                        <p style="text-align: justify;">A GAMSTOP permite que você defina controles para ajudar a restringir as suas atividades de jogos de azar online.</p>
                        <p style="text-align: justify;">Cadastre-se no serviço. Você poderá escolher ser impedido de usar sites de jogos de azar, bem como aplicativos gerenciados por empresas licenciadas na Grã-Bretanha, pelo período de tempo da sua escolha. Cadastre-se <a href="https://www.gamstop.co.uk/personal-details/" rel="noopener noreferrer" target="_blank">aqui</a>.</p>
                        <p style="text-align: justify;">Depois de se cadastrar, você receberá um e-mail informando todos os detalhes da sua exclusão de atividades de jogos de azar. Pode levar 24 horas para a sua auto-exclusão ser efetivada.</p>
                        <p style="text-align: justify;">Depois deste período, você será excluído(a) de qualquer empresa de jogos de azar online licenciada na Grã-Bretanha.</p>
                        <p style="text-align: justify;">Dependendo da opção que escolher, você se auto excluirá por um período de seis meses, um ano ou cinco anos. Uma vez que o período de duração mínima termine, você pode voltar ao GAMSTOP e solicitar que a Auto-exclusão seja suspensa. Depois disso, você passará pelo processo relevante.&nbsp;</p>
                        <p style="text-align: justify;">Você encontrará mais informações detalhadas sobre a GAMSTOP&nbsp;<a href="https://www.gamstop.co.uk/" rel="noopener noreferrer" target="_blank">aqui</a>, no website deles.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="software" style="text-align: justify;">Bloquear acesso a websites de jogos de azar online com o GamBlock</h2>
                        <p style="text-align: justify;">Se você quer bloquear acesso à sites de jogos de azar online através do seu computador, nós recomendamos que use o software da GamBlock. Por favor, leia mais em <a href="http://www.gamblock.com/index.html" rel="noopener noreferrer" target="_blank">www.gamblock.com</a>.</p>'
        ],
        [
            'language' => 'cl',
            'alias' => 'simple.644.html',
            'value' => '<h1>Juego responsable</h1>
                        <p style="text-align: justify;">Dbet.com ve el juego de casino como una manera positiva de entretenimiento para adultos. La mayoría de los jugadores comparten esta opinión, pero hay un pequeño porcentaje que deja que el juego se convierta en una parte importante de sus vidas.&nbsp; Si sientes que tu juego es un problema, dbet.com te puede ayudar a tener un juego responsable. Podemos ajustar la cantidad de dinero que puedes depositar, perder o apostar de acuerdo con tu solicitud. Además, si lo requieres, dbet.com puede cerrar tu cuenta durante un período de tiempo elegido. <a href="/es/customer-service" target="_self">Contáctanos</a> y tomaremos las medidas necesarias que mejor se adapten a ti.</p>
                        <ol class="page-sections_link_box">
                        <li><strong><a href="#grupos-de-soporte">Grupos de Soporte</a></strong></li>
                        <li><strong><a href="#juegos-menores">Juegos para menores de edad</a></strong></li>
                        <li><strong><a href="#autoexclusion-del-jugador">Autoexclusión del jugador</a></strong></li>
                        <li><strong><a href="#manten-el-control">Mantén el control</a></strong></li>
                        <li><strong><a href="#bloquear-acceso">Bloquear el acceso a los juegos de azar en línea</a></strong></li>
                        </ol>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="grupos-de-soporte" style="text-align: justify;">Grupos de Soporte</h2>
                        <p style="text-align: justify;">Si sientes que necesitas hablar con un profesional acerca de tu juego o sientes que necesitas asesoramiento, contacta con alguna de estas organizaciones independientes para solicitar ayuda:&nbsp;</p>
                        <h3 style="text-align: justify;">GamCare</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="http://www.gamcare.org.uk" rel="noopener noreferrer" target="_blank">www.gamcare.org.uk</a>&nbsp;<br><strong>Correo electrónico</strong>:&nbsp;<a href="mailto:info@gamcare.org.uk" rel="noopener noreferrer" target="_blank">info@gamcare.org.uk</a>&nbsp;<br><strong>Línea de ayuda</strong>:&nbsp;(+44) 0808 8020 133<br><strong>Dirección</strong>:&nbsp;2 &amp; 3 Baden Place, Crosby Row, London, SE1 1YW</p>
                        <h3 style="text-align: justify;">GambleAware</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="https://www.begambleaware.org/" rel="noopener noreferrer" target="_blank">www.begambleaware.org</a><br><strong>Línea de ayuda</strong>:&nbsp;(+44) 0808 8020 133&nbsp;</p>
                        <h3 style="text-align: justify;">Gordonhouse</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="http://www.gamblingtherapy.org" rel="noopener noreferrer" target="_blank">www.gamblingtherapy.org</a>&nbsp;<br><strong>Correo electrónico</strong>:&nbsp;<a href="mailto:webmaster@gamblingtherapy.org" rel="noopener noreferrer" target="_blank">webmaster@gamblingtherapy.org</a>&nbsp;<br><strong>Soporte en línea</strong>:&nbsp;<a href="https://v2.zopim.com/widget/livechat.html?api_calls=%5B%5D&amp;hostname=www.gamblingtherapy.org&amp;key=3rmcboQG3XbDlIIe3WFgsE4tyIa4jui6&amp;lang=es&amp;%20" rel="noopener noreferrer" target="_blank">[Aquí]</a><br><strong>Dirección</strong>:&nbsp;The Gambling Therapy pilot, Gordon House Association, 114 Wellington Road, Dudley, West Midlands, DY1 1UB, UK</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="juegos-menores" style="text-align: justify;">Juegos para menores de edad</h2>
                        <p style="text-align: justify;">Jugar en dbet.com está restringido a personas menores de 18 años o a personas menores de edad según la de edad legal en tu jurisdicción. Se tendrá en cuenta la edad más restrictiva.&nbsp;<br><br>Todos los detalles que te solicitamos se utilizan para verificar tu identidad, esto es tanto para tu propia protección como para la nuestra. Si tienes alguna pregunta, no dudes en ponerte en <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">contacto</a> con nosotros en cualquier momento.</p>
                        <h3 style="text-align: justify;">Protección de menores</h3>
                        <p style="text-align: justify;">El juego de menores es ilegal. Si eres menor de 18 años o no tienes la edad mínima requerida que exige la ley de la jurisdicción aplicable, debes tener en cuenta que puedes estar cometiendo un delito. La compañía no entretiene ni paga ganancias a jugadores que no tienen la edad legal requerida.</p>
                        <p style="text-align: justify;">Con el fácil acceso a Internet en los hogares de todo el mundo, los juegos en línea responsables dependen en gran medida de la educación responsable.&nbsp;<br><br>Con el fin de garantizar la seguridad de los niños en Internet, Kungaslottet alienta a sus jugadores a utilizar el software de filtrado para evitar que los menores accedan a cierto material en línea.</p>
                        <p style="text-align: justify;"><a href="http://www.gamcare.org.uk/get-advice/what-can-you-do/blocking-software#.VmbUbPmrSM9" rel="noopener noreferrer" target="_blank">Gamecare</a><br><a href="https://www.netnanny.com/features/internet-filter/" rel="noopener noreferrer" target="_blank">Netnanny</a></p>
                        <h4 style="text-align: justify;">Consejos para los padres:</h4>
                        <ul style="text-align: justify;">
                        <li>No dejes tu ordenador desatendido cuando el software del casino esté funcionando.</li>
                        <li>Protege con contraseña el programa del casino.</li>
                        <li>No permitas que personas menores de 18 años participen en ninguna actividad de juegos de azar.</li>
                        <li>Mantén tu número de cuenta del casino y tu (s) tarjeta (s) de crédito fuera del alcance de los niños.</li>
                        <li>No guardes las contraseñas en tu ordenador. En lugar de esto, escríbelas.</li>
                        <li>Limita la cantidad de tiempo que tus hijos pasan en línea y utiliza software para evitar que accedan a material inapropiado.</li>
                        </ul>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="autoexclusion-del-jugador" style="text-align: justify;">Autoexclusión del jugador&nbsp;&nbsp;</h2>
                        <p style="text-align: justify;">Kungaslottet reconoce que, si bien la mayoría de los jugadores apuestan por entretenimiento, los juegos de azar pueden ser perjudiciales para una pequeña cantidad de personas. Estamos comprometidos a crear un entorno de juego responsable.&nbsp;</p>
                        <p style="text-align: justify;">Si te preocupa tu comportamiento en los juegos de azar, por favor considera realizar una autoexclusión.</p>
                        <p style="text-align: justify;">Kungaslottet se compromete a brindar a nuestros jugadores una experiencia de juego agradable y segura. Si eliges ingresar una autoexclusión, tu cuenta de juego en dbet.com se autoexcluirá de inmediato para el período elegido. Esto significa que no podrás iniciar sesión, depositar o jugar en dbet.com mientras estés auto excluido. Ten en cuenta que no podrás volver a abrir tu cuenta hasta que el período de autoexclusión elegido haya expirado.</p>
                        <p style="text-align: justify;">También tomaremos todas las medidas razonables para asegurarnos de que no recibas ningún material promocional durante este tiempo. Sin embargo, si utilizas canales de redes sociales, te recomendamos que tomes medidas para asegurarte de no recibir nuestras noticias o actualizaciones.</p>
                        <p style="text-align: justify;">Tienes la opción de autoexcluirte por un período mínimo de seis meses hasta cinco años.&nbsp;</p>
                        <p style="text-align: justify;">También te recomendamos que te autoexcluyas con cualquier otro operador con el que puedas estar registrado.</p>
                        <p style="text-align: justify;">Para solicitar una autoexclusión, comunícate con nuestro equipo de soporte por <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">correo electrónico o vía chat</a>. O puedes ingresar una autoexclusión en "Mi perfil" -&gt; "Juego responsable" mientras estás conectado a tu cuenta en dbet.com.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="manten-el-control" style="text-align: justify;">Mantén el control</h2>
                        <p style="text-align: justify;">Reconocemos que, aunque la mayoría de los jugadores apuestan por entretenimiento, hay un pequeño número de personas que pueden perder el control de sus juegos de azar. Kungaslottet apoya totalmente el juego responsable y se asegura que continúes disfrutando de un juego seguro y manejable.&nbsp; Por favor, ten en cuenta lo siguiente:</p>
                        <ul>li&gt;El juego debe ser visto como un entretenimiento y no como un medio para ganar dinero.
                        <li>Lleva un registro del tiempo y el dinero que gastas mientras juegas.</li>
                        <li>Establece límites que te ayuden a mantener el control.</li>
                        </ul>
                        <p style="text-align: justify;">En la siguiente lista encontrarás una serie de medidas y herramientas para ayudarte a controlar el tiempo y el dinero que gastas en dbet.com:</p>
                        <ul>
                        <li>Límite de apuesta.</li>
                        <li>Límite de depósito para delimitar cuánto puedes depositar en tu cuenta en dbet.com.</li>
                        <li>El límite de pérdida te ayuda a no gastar más de tu presupuesto.</li>
                        <li>Límite máximo de apuesta para protegerte contra una tirada o apuesta&nbsp; mayor a la prevista realizada en forma accidental.</li>
                        <li>Límite de tiempo de espera para evitar que permanezcas más tiempo del previsto en una sesión de juego.</li>
                        <li>REvaluación realista para recibir información sobre tus ganancias netas en tu sesión actual.</li>
                        <li>Bloqueo de cuenta es una herramienta que puedes usar si necesitas un breve descanso en tu juego.</li>
                        </ul>
                        <p style="text-align: justify;">Antes de comenzar a jugar, te recomendamos que eches un vistazo y hagas uso de nuestros límites. Para obtener más información sobre estas herramientas y cómo configurarlas, inicia sesión en tu cuenta y ve a "Mi perfil" -&gt; "Juego responsable".</p>
                        <p style="text-align: justify;">Si necesitas un descanso del juego, puedes configurar la autoexclusión desde la sección "Mi cuenta" mientras estás conectado. También puedes contactar con nuestro departamento de Atención al Cliente por <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">correo electrónico o vía chat</a>.&nbsp; &nbsp;</p>
                        <p style="text-align: justify;">Si necesitas hablar con alguien sobre cualquier inquietud que puedas tener con tus juegos de azar comunícate con una de las organizaciones, que puedes encontrar en <a href="#grupos-de-soporte">Grupos de Soporte</a>.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="bloquear-acceso" style="text-align: justify;">Bloquea el acceso a los sitios de juegos en línea con GamBlock</h2>
                        <p style="text-align: justify;">Si quieres bloquear el acceso a los sitios de juegos en línea desde tu ordenador, te recomendamos que uses un software como GamBlock. Por favor, lee más en&nbsp;<a href="http://www.gamblock.com/index.html" rel="noopener noreferrer" target="_blank">www.gamblock.com</a>.</p>'
        ],
        [
            'language' => 'es',
            'alias' => 'simple.644.html',
            'value' => '<h1>Juego responsable</h1>
                        <p style="text-align: justify;">Dbet.com ve el juego de casino como una manera positiva de entretenimiento para adultos. La mayoría de los jugadores comparten esta opinión, pero hay un pequeño porcentaje que deja que el juego se convierta en una parte importante de sus vidas.&nbsp; Si sientes que tu juego es un problema, dbet.com te puede ayudar a tener un juego responsable. Podemos ajustar la cantidad de dinero que puedes depositar, perder o apostar de acuerdo con tu solicitud. Además, si lo requieres, dbet.com puede cerrar tu cuenta durante un período de tiempo elegido. <a href="/es/customer-service" target="_self">Contáctanos</a> y tomaremos las medidas necesarias que mejor se adapten a ti.</p>
                        <ol class="page-sections_link_box">
                        <li><strong><a href="#grupos-de-soporte">Grupos de Soporte</a></strong></li>
                        <li><strong><a href="#juegos-menores">Juegos para menores de edad</a></strong></li>
                        <li><strong><a href="#autoexclusion-del-jugador">Autoexclusión del jugador</a></strong></li>
                        <li><strong><a href="#manten-el-control">Mantén el control</a></strong></li>
                        <li><strong><a href="#bloquear-acceso">Bloquear el acceso a los juegos de azar en línea</a></strong></li>
                        </ol>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="grupos-de-soporte" style="text-align: justify;">Grupos de Soporte</h2>
                        <p style="text-align: justify;">Si sientes que necesitas hablar con un profesional acerca de tu juego o sientes que necesitas asesoramiento, contacta con alguna de estas organizaciones independientes para solicitar ayuda:&nbsp;</p>
                        <h3 style="text-align: justify;">GamCare</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="http://www.gamcare.org.uk" rel="noopener noreferrer" target="_blank">www.gamcare.org.uk</a>&nbsp;<br><strong>Correo electrónico</strong>:&nbsp;<a href="mailto:info@gamcare.org.uk" rel="noopener noreferrer" target="_blank">info@gamcare.org.uk</a>&nbsp;<br><strong>Línea de ayuda</strong>:&nbsp;(+44) 0808 8020 133<br><strong>Dirección</strong>:&nbsp;2 &amp; 3 Baden Place, Crosby Row, London, SE1 1YW</p>
                        <h3 style="text-align: justify;">GambleAware</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="https://www.begambleaware.org/" rel="noopener noreferrer" target="_blank">www.begambleaware.org</a><br><strong>Línea de ayuda</strong>:&nbsp;(+44) 0808 8020 133&nbsp;</p>
                        <h3 style="text-align: justify;">Gordonhouse</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="http://www.gamblingtherapy.org" rel="noopener noreferrer" target="_blank">www.gamblingtherapy.org</a>&nbsp;<br><strong>Correo electrónico</strong>:&nbsp;<a href="mailto:webmaster@gamblingtherapy.org" rel="noopener noreferrer" target="_blank">webmaster@gamblingtherapy.org</a>&nbsp;<br><strong>Soporte en línea</strong>:&nbsp;<a href="https://v2.zopim.com/widget/livechat.html?api_calls=%5B%5D&amp;hostname=www.gamblingtherapy.org&amp;key=3rmcboQG3XbDlIIe3WFgsE4tyIa4jui6&amp;lang=es&amp;%20" rel="noopener noreferrer" target="_blank">[Aquí]</a><br><strong>Dirección</strong>:&nbsp;The Gambling Therapy pilot, Gordon House Association, 114 Wellington Road, Dudley, West Midlands, DY1 1UB, UK</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="juegos-menores" style="text-align: justify;">Juegos para menores de edad</h2>
                        <p style="text-align: justify;">Jugar en dbet.com está restringido a personas menores de 18 años o a personas menores de edad según la de edad legal en tu jurisdicción. Se tendrá en cuenta la edad más restrictiva.&nbsp;<br><br>Todos los detalles que te solicitamos se utilizan para verificar tu identidad, esto es tanto para tu propia protección como para la nuestra. Si tienes alguna pregunta, no dudes en ponerte en <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">contacto</a> con nosotros en cualquier momento.</p>
                        <h3 style="text-align: justify;">Protección de menores</h3>
                        <p style="text-align: justify;">El juego de menores es ilegal. Si eres menor de 18 años o no tienes la edad mínima requerida que exige la ley de la jurisdicción aplicable, debes tener en cuenta que puedes estar cometiendo un delito. La compañía no entretiene ni paga ganancias a jugadores que no tienen la edad legal requerida.</p>
                        <p style="text-align: justify;">Con el fácil acceso a Internet en los hogares de todo el mundo, los juegos en línea responsables dependen en gran medida de la educación responsable.&nbsp;<br><br>Con el fin de garantizar la seguridad de los niños en Internet, Kungaslottet alienta a sus jugadores a utilizar el software de filtrado para evitar que los menores accedan a cierto material en línea.</p>
                        <p style="text-align: justify;"><a href="http://www.gamcare.org.uk/get-advice/what-can-you-do/blocking-software#.VmbUbPmrSM9" rel="noopener noreferrer" target="_blank">Gamecare</a><br><a href="https://www.netnanny.com/features/internet-filter/" rel="noopener noreferrer" target="_blank">Netnanny</a></p>
                        <h4 style="text-align: justify;">Consejos para los padres:</h4>
                        <ul style="text-align: justify;">
                        <li>No dejes tu ordenador desatendido cuando el software del casino esté funcionando.</li>
                        <li>Protege con contraseña el programa del casino.</li>
                        <li>No permitas que personas menores de 18 años participen en ninguna actividad de juegos de azar.</li>
                        <li>Mantén tu número de cuenta del casino y tu (s) tarjeta (s) de crédito fuera del alcance de los niños.</li>
                        <li>No guardes las contraseñas en tu ordenador. En lugar de esto, escríbelas.</li>
                        <li>Limita la cantidad de tiempo que tus hijos pasan en línea y utiliza software para evitar que accedan a material inapropiado.</li>
                        </ul>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="autoexclusion-del-jugador" style="text-align: justify;">Autoexclusión del jugador&nbsp;&nbsp;</h2>
                        <p style="text-align: justify;">Kungaslottet reconoce que, si bien la mayoría de los jugadores apuestan por entretenimiento, los juegos de azar pueden ser perjudiciales para una pequeña cantidad de personas. Estamos comprometidos a crear un entorno de juego responsable.&nbsp;</p>
                        <p style="text-align: justify;">Si te preocupa tu comportamiento en los juegos de azar, por favor considera realizar una autoexclusión.</p>
                        <p style="text-align: justify;">Kungaslottet se compromete a brindar a nuestros jugadores una experiencia de juego agradable y segura. Si eliges ingresar una autoexclusión, tu cuenta de juego en dbet.com se autoexcluirá de inmediato para el período elegido. Esto significa que no podrás iniciar sesión, depositar o jugar en dbet.com mientras estés auto excluido. Ten en cuenta que no podrás volver a abrir tu cuenta hasta que el período de autoexclusión elegido haya expirado.</p>
                        <p style="text-align: justify;">También tomaremos todas las medidas razonables para asegurarnos de que no recibas ningún material promocional durante este tiempo. Sin embargo, si utilizas canales de redes sociales, te recomendamos que tomes medidas para asegurarte de no recibir nuestras noticias o actualizaciones.</p>
                        <p style="text-align: justify;">Tienes la opción de autoexcluirte por un período mínimo de seis meses hasta cinco años.&nbsp;</p>
                        <p style="text-align: justify;">También te recomendamos que te autoexcluyas con cualquier otro operador con el que puedas estar registrado.</p>
                        <p style="text-align: justify;">Para solicitar una autoexclusión, comunícate con nuestro equipo de soporte por <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">correo electrónico o vía chat</a>. O puedes ingresar una autoexclusión en "Mi perfil" -&gt; "Juego responsable" mientras estás conectado a tu cuenta en dbet.com.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="manten-el-control" style="text-align: justify;">Mantén el control</h2>
                        <p style="text-align: justify;">Reconocemos que, aunque la mayoría de los jugadores apuestan por entretenimiento, hay un pequeño número de personas que pueden perder el control de sus juegos de azar. Kungaslottet apoya totalmente el juego responsable y se asegura que continúes disfrutando de un juego seguro y manejable.&nbsp; Por favor, ten en cuenta lo siguiente:</p>
                        <ul>
                        <li>El juego debe ser visto como un entretenimiento y no como un medio para ganar dinero.</li>
                        <li>Lleva un registro del tiempo y el dinero que gastas mientras juegas.</li>
                        <li>Establece límites que te ayuden a mantener el control.</li>
                        </ul>
                        <p style="text-align: justify;">En la siguiente lista encontrarás una serie de medidas y herramientas para ayudarte a controlar el tiempo y el dinero que gastas en dbet.com:</p>
                        <ul>
                        <li>Límite de apuesta.</li>
                        <li>Límite de depósito para delimitar cuánto puedes depositar en tu cuenta en dbet.com.</li>
                        <li>El límite de pérdida te ayuda a no gastar más de tu presupuesto.</li>
                        <li>Límite máximo de apuesta para protegerte contra una tirada o apuesta&nbsp; mayor a la prevista realizada en forma accidental.</li>
                        <li>Límite de tiempo de espera para evitar que permanezcas más tiempo del previsto en una sesión de juego.</li>
                        <li>REvaluación realista para recibir información sobre tus ganancias netas en tu sesión actual.</li>
                        <li>Bloqueo de cuenta es una herramienta que puedes usar si necesitas un breve descanso en tu juego.</li>
                        </ul>
                        <p style="text-align: justify;">Antes de comenzar a jugar, te recomendamos que eches un vistazo y hagas uso de nuestros límites. Para obtener más información sobre estas herramientas y cómo configurarlas, inicia sesión en tu cuenta y ve a "Mi perfil" -&gt; "Juego responsable".</p>
                        <p style="text-align: justify;">Si necesitas un descanso del juego, puedes configurar la autoexclusión desde la sección "Mi cuenta" mientras estás conectado. También puedes contactar con nuestro departamento de Atención al Cliente por <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">correo electrónico o vía chat</a>.&nbsp; &nbsp;</p>
                        <p style="text-align: justify;">Si necesitas hablar con alguien sobre cualquier inquietud que puedas tener con tus juegos de azar comunícate con una de las organizaciones, que puedes encontrar en <a href="#grupos-de-soporte">Grupos de Soporte</a>.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="bloquear-acceso" style="text-align: justify;">Bloquea el acceso a los sitios de juegos en línea con GamBlock</h2>
                        <p style="text-align: justify;">Si quieres bloquear el acceso a los sitios de juegos en línea desde tu ordenador, te recomendamos que uses un software como GamBlock. Por favor, lee más en&nbsp;<a href="http://www.gamblock.com/index.html" rel="noopener noreferrer" target="_blank">www.gamblock.com</a>.</p>'
        ],
        [
            'language' => 'on',
            'alias' => 'simple.644.html',
            'value' => '<h1>Responsible Gambling</h1>
                        <p>Kungaslottet is committed supporting and encouraging responsible gambling. For most, online gaming is an exciting and sustainable form of entertainment. There is however a small percentage of players who have a negative experience, where play is no longer enjoyable. Kungaslottet is committed to providing players with a wealth of information to educate and aid players in their journey to safer gambling and responsible play. Kungaslottet offers various responsible gambling tools and limits to help players gamble responsibly.</p>
                        <ol class="page-sections_link_box">
                        <li><a href="#support-groups">Support Groups</a></li>
                        <li><a href="#preventing-underage-gambling">Preventing Underage Gambling</a></li>
                        <li><a href="#short-term-break">Short Term Break</a></li>
                        <li><a href="#self-exclusion">Self-Exclusion</a></li>
                        <li><a href="#maintain-control">Maintain Control</a></li>
                        <li><a href="#block-access">Block Access to Online Gambling</a></li>
                        </ol>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="support-groups">Support Groups</h2>
                        <p>If you feel that you need to talk to a professional about your gambling or you feel that you need counselling, contact one of these independent organisations for help:&nbsp;</p>
                        <p><strong>ConnexOntario</strong></p>
                        <p><strong>Web:</strong>&nbsp;<a href="http://www.connexontario.ca/" rel="noopener noreferrer" target="_blank">www.connexontario.ca</a><br><strong>Helpline:</strong>&nbsp;1-866-531-2600</p>
                        <p><em>Additional resources</em></p>
                        <p><strong>Gambling Therapy</strong></p>
                        <p><strong>Web:</strong>&nbsp;<a href="https://www.gamblingtherapy.org/" rel="noopener noreferrer" target="_blank">https://www.gamblingtherapy.org/</a><br><strong>E-mail:</strong>&nbsp;<a href="mailto:support@gamblingtherapy.org">support@gamblingtherapy.org</a></p>
                        <p><strong>Gamtalk</strong></p>
                        <p><strong>Web:</strong>&nbsp;<a href="https://www.gamtalk.org/" rel="noopener noreferrer" target="_blank">Home - GamTalk</a></p>
                        <p><strong>Recoverme: download the app on Apple App Store or Google Play Store</strong></p>
                        <p style="text-align: justify;"><a name="preventing-underage-gambling"></a></p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="preventing-underage-gambling" style="text-align: justify;">Preventing Underage Gambling</h2>
                        <p>You must be the age of 19 or old to play at dbet.com.</p>
                        <p>In order to ensure minors or those under the legal age of gambling cannot access online gaming, Kungaslottet encourages players to make use of filtering software to prevent underage players from accessing certain online material.</p>
                        <p><strong>Tips for Parents:</strong></p>
                        <ul>
                        <li>Do not leave your computer unattended when your casino software is running.</li>
                        <li>Password-protect your computer.</li>
                        <li>Do not allow persons under 19 to participate in any gambling activity.</li>
                        <li>Keep your log in details safe.</li>
                        <li>Do not save passwords on your computer..</li>
                        <li>Make use of software to underage persons from accessing online gaming sites.</li>
                        <li><a href="https://www.netnanny.com/features/internet-filter/" rel="noopener noreferrer" target="_blank">NetNanny</a> offers information and services about internet filters to moderate online access.</li>
                        </ul>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="short-term-break">Short Term Break</h2>
                        <p>To take a short term break from your dbet.com account, go to "My Profile" -&gt; "Responsible Gaming" whilst logged in to your account.</p>
                        <p>You may choose to take a break from gambling for a shorter period of time choosing the ‘Lock account X Days’ option for 24h, 1 week, 1month, 2 months, 3 months or other (input your preferred period in the number of days you would like a short term break for). Once selected, the break will take place immediately. During this period, you will be unable to access your account. Your account will not be re-opened under any circumstance.</p>
                        <p>Once you have taken a break, you must not try to log in to your account or create a new account during the exclusion period. You must not attempt to gamble using another customer’s account at any time.</p>
                        <p>A self-lock takes effect immediately and during the self-lock you are unable to login, deposit or play at dbet.com.</p>
                        <p>You may choose to self-lock from dbet.com at any time. From the day you select to self-lock yourself, you shall no longer be able to receive bonuses and/other promotional offers from dbet.com.</p>
                        <p>Once the self-lock period has ended, your account will open automatically, and you will once again have access to your player account.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="self-exclusion">Self-Exclusion</h2>
                        <p>To exclude your dbet.com account, go to "My Profile" -&gt; "Responsible Gaming" whilst logged in to your account.</p>
                        <p>You may choose to exclude from dbet.com at any time. You may choose to exclude yourself from gambling for a period of 6 months, 1 year, 2 years, 3 years or 5 years. You can request to extend your exclusion period at any time by contacting dbet.com. Once selected, exclusion will take place immediately. During this period, you will be unable to access your account. Your account will not be re-opened under any circumstance. Should you choose to exclude, dbet.com recommends do this for all other operators with which you have an online gaming account.</p>
                        <p>A self-exclusion takes effect immediately and during the self-exclusion you are unable to login, deposit or play at dbet.com. From the day you select to exclude yourself, you shall no longer be able to receive bonuses and/other promotional offers from dbet.com.</p>
                        <p>Once you have excluded, you must not try to log in to your account or create a new account during the exclusion period. You must not attempt to gamble using another customer’s account at any time.</p>
                        <p>When your period exclusion ends, you will be required to contact customer support at&nbsp;<a href="mailto:on.support@dbet.com">on.support@dbet.com</a>&nbsp;or live chat to request reactivation of the account. Dbet.com will provide you with information on the impact of lifting your exclusion, and signpost you to player protection information and tools. You will then be requested and required to pass the reactivation process, which consists of successfully answering a series of questions</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="maintain-control">Maintain Control</h2>
                        <p>Kungaslottet provides information and tools to help players play responsibly. Players must take responsibility and control of their gambling activities. Always consider the information at hand and set self-imposed limits where necessary. Remember:</p>
                        <ul>
                        <li>Gambling is a form of entertainment, not a means to generate income.</li>
                        <li>Always keep track of the time and money spent gambling.</li>
                        <li>Use financial and limits to help stay in control.</li>
                        <li>You can take time away - consider a break if you feel you are no longer playing responsibly.</li>
                        </ul>
                        <p>Dbet.com offers the following to help you stay in control of the time and money you spend:.&nbsp;</p>
                        <ul>
                        <li>Wager limit to control how much you bet.</li>
                        <li>Deposit limit to limit how much you can deposit into your account on dbet.com.</li>
                        <li>Loss limit helps you play within budget.</li>
                        <li>Max bet limit to cap your individual bet amounts.</li>
                        <li>Time-out limit to limit time spent in a game.</li>
                        </ul>
                        <p>Before you start playing, dbet.com recommends you consider and make use of our limits. For more information about these tools and how to set them please login to your account and go to "My Profile" -&gt; "Responsible Gaming".</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="block-access">Block Access to Online Gambling</h2>
                        <p>If you want to block access to online gambling sites from your device, we recommend you to use GamBlock blocking software. Find out more at&nbsp;<a href="http://www.gamblock.com/index.html" rel="noopener noreferrer" target="_blank">www.gamblock.com</a>.</p>
                        <p><br><br></p>'
        ],
        [
            'language' => 'pe',
            'alias' => 'simple.644.html',
            'value' => '<h1>Juego responsable</h1>
                        <p style="text-align: justify;">Dbet.com ve el juego de casino como una manera positiva de entretenimiento para adultos. La mayoría de los jugadores comparten esta opinión, pero hay un pequeño porcentaje que deja que el juego se convierta en una parte importante de sus vidas.&nbsp; Si sientes que tu juego es un problema, dbet.com te puede ayudar a tener un juego responsable. Podemos ajustar la cantidad de dinero que puedes depositar, perder o apostar de acuerdo con tu solicitud. Además, si lo requieres, dbet.com puede cerrar tu cuenta durante un período de tiempo elegido. <a href="/es/customer-service" target="_self">Contáctanos</a> y tomaremos las medidas necesarias que mejor se adapten a ti.</p>
                        <ol class="page-sections_link_box">
                        <li><strong><a href="#grupos-de-soporte">Grupos de Soporte</a></strong></li>
                        <li><strong><a href="#juegos-menores">Juegos para menores de edad</a></strong></li>
                        <li><strong><a href="#autoexclusion-del-jugador">Autoexclusión del jugador</a></strong></li>
                        <li><strong><a href="#manten-el-control">Mantén el control</a></strong></li>
                        <li><strong><a href="#bloquear-acceso">Bloquear el acceso a los juegos de azar en línea</a></strong></li>
                        </ol>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="grupos-de-soporte" style="text-align: justify;">Grupos de Soporte</h2>
                        <p style="text-align: justify;">Si sientes que necesitas hablar con un profesional acerca de tu juego o sientes que necesitas asesoramiento, contacta con alguna de estas organizaciones independientes para solicitar ayuda:&nbsp;</p>
                        <h3 style="text-align: justify;">GamCare</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="http://www.gamcare.org.uk" rel="noopener noreferrer" target="_blank">www.gamcare.org.uk</a>&nbsp;<br><strong>Correo electrónico</strong>:&nbsp;<a href="mailto:info@gamcare.org.uk" rel="noopener noreferrer" target="_blank">info@gamcare.org.uk</a>&nbsp;<br><strong>Línea de ayuda</strong>:&nbsp;(+44) 0808 8020 133<br><strong>Dirección</strong>:&nbsp;2 &amp; 3 Baden Place, Crosby Row, London, SE1 1YW</p>
                        <h3 style="text-align: justify;">GambleAware</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="https://www.begambleaware.org/" rel="noopener noreferrer" target="_blank">www.begambleaware.org</a><br><strong>Línea de ayuda</strong>:&nbsp;(+44) 0808 8020 133&nbsp;</p>
                        <h3 style="text-align: justify;">Gordonhouse</h3>
                        <p style="text-align: justify;"><strong>Web</strong>:&nbsp;<a href="http://www.gamblingtherapy.org" rel="noopener noreferrer" target="_blank">www.gamblingtherapy.org</a>&nbsp;<br><strong>Correo electrónico</strong>:&nbsp;<a href="mailto:webmaster@gamblingtherapy.org" rel="noopener noreferrer" target="_blank">webmaster@gamblingtherapy.org</a>&nbsp;<br><strong>Soporte en línea</strong>:&nbsp;<a href="https://v2.zopim.com/widget/livechat.html?api_calls=%5B%5D&amp;hostname=www.gamblingtherapy.org&amp;key=3rmcboQG3XbDlIIe3WFgsE4tyIa4jui6&amp;lang=es&amp;%20" rel="noopener noreferrer" target="_blank">[Aquí]</a><br><strong>Dirección</strong>:&nbsp;The Gambling Therapy pilot, Gordon House Association, 114 Wellington Road, Dudley, West Midlands, DY1 1UB, UK</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="juegos-menores" style="text-align: justify;">Juegos para menores de edad</h2>
                        <p style="text-align: justify;">Jugar en dbet.com está restringido a personas menores de 18 años o a personas menores de edad según la de edad legal en tu jurisdicción. Se tendrá en cuenta la edad más restrictiva.&nbsp;<br><br>Todos los detalles que te solicitamos se utilizan para verificar tu identidad, esto es tanto para tu propia protección como para la nuestra. Si tienes alguna pregunta, no dudes en ponerte en <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">contacto</a> con nosotros en cualquier momento.</p>
                        <h3 style="text-align: justify;">Protección de menores</h3>
                        <p style="text-align: justify;">El juego de menores es ilegal. Si eres menor de 18 años o no tienes la edad mínima requerida que exige la ley de la jurisdicción aplicable, debes tener en cuenta que puedes estar cometiendo un delito. La compañía no entretiene ni paga ganancias a jugadores que no tienen la edad legal requerida.</p>
                        <p style="text-align: justify;">Con el fácil acceso a Internet en los hogares de todo el mundo, los juegos en línea responsables dependen en gran medida de la educación responsable.&nbsp;<br><br>Con el fin de garantizar la seguridad de los niños en Internet, Kungaslottet alienta a sus jugadores a utilizar el software de filtrado para evitar que los menores accedan a cierto material en línea.</p>
                        <p style="text-align: justify;"><a href="http://www.gamcare.org.uk/get-advice/what-can-you-do/blocking-software#.VmbUbPmrSM9" rel="noopener noreferrer" target="_blank">Gamecare</a><br><a href="https://www.netnanny.com/features/internet-filter/" rel="noopener noreferrer" target="_blank">Netnanny</a></p>
                        <h4 style="text-align: justify;">Consejos para los padres:</h4>
                        <ul style="text-align: justify;">
                        <li>No dejes tu ordenador desatendido cuando el software del casino esté funcionando.</li>
                        <li>Protege con contraseña el programa del casino.</li>
                        <li>No permitas que personas menores de 18 años participen en ninguna actividad de juegos de azar.</li>
                        <li>Mantén tu número de cuenta del casino y tu (s) tarjeta (s) de crédito fuera del alcance de los niños.</li>
                        <li>No guardes las contraseñas en tu ordenador. En lugar de esto, escríbelas.</li>
                        <li>Limita la cantidad de tiempo que tus hijos pasan en línea y utiliza software para evitar que accedan a material inapropiado.</li>
                        </ul>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="autoexclusion-del-jugador" style="text-align: justify;">Autoexclusión del jugador&nbsp;&nbsp;</h2>
                        <p style="text-align: justify;">Kungaslottet reconoce que, si bien la mayoría de los jugadores apuestan por entretenimiento, los juegos de azar pueden ser perjudiciales para una pequeña cantidad de personas. Estamos comprometidos a crear un entorno de juego responsable.&nbsp;</p>
                        <p style="text-align: justify;">Si te preocupa tu comportamiento en los juegos de azar, por favor considera realizar una autoexclusión.</p>
                        <p style="text-align: justify;">Kungaslottet se compromete a brindar a nuestros jugadores una experiencia de juego agradable y segura. Si eliges ingresar una autoexclusión, tu cuenta de juego en dbet.com se autoexcluirá de inmediato para el período elegido. Esto significa que no podrás iniciar sesión, depositar o jugar en dbet.com mientras estés auto excluido. Ten en cuenta que no podrás volver a abrir tu cuenta hasta que el período de autoexclusión elegido haya expirado.</p>
                        <p style="text-align: justify;">También tomaremos todas las medidas razonables para asegurarnos de que no recibas ningún material promocional durante este tiempo. Sin embargo, si utilizas canales de redes sociales, te recomendamos que tomes medidas para asegurarte de no recibir nuestras noticias o actualizaciones.</p>
                        <p style="text-align: justify;">Tienes la opción de autoexcluirte por un período mínimo de seis meses hasta cinco años.&nbsp;</p>
                        <p style="text-align: justify;">También te recomendamos que te autoexcluyas con cualquier otro operador con el que puedas estar registrado.</p>
                        <p style="text-align: justify;">Para solicitar una autoexclusión, comunícate con nuestro equipo de soporte por <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">correo electrónico o vía chat</a>. O puedes ingresar una autoexclusión en "Mi perfil" -&gt; "Juego responsable" mientras estás conectado a tu cuenta en dbet.com.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="manten-el-control" style="text-align: justify;">Mantén el control</h2>
                        <p style="text-align: justify;">Reconocemos que, aunque la mayoría de los jugadores apuestan por entretenimiento, hay un pequeño número de personas que pueden perder el control de sus juegos de azar. Kungaslottet apoya totalmente el juego responsable y se asegura que continúes disfrutando de un juego seguro y manejable.&nbsp; Por favor, ten en cuenta lo siguiente:</p>
                        <ul>li&gt;El juego debe ser visto como un entretenimiento y no como un medio para ganar dinero.
                        <li>Lleva un registro del tiempo y el dinero que gastas mientras juegas.</li>
                        <li>Establece límites que te ayuden a mantener el control.</li>
                        </ul>
                        <p style="text-align: justify;">En la siguiente lista encontrarás una serie de medidas y herramientas para ayudarte a controlar el tiempo y el dinero que gastas en dbet.com:</p>
                        <ul>
                        <li>Límite de apuesta.</li>
                        <li>Límite de depósito para delimitar cuánto puedes depositar en tu cuenta en dbet.com.</li>
                        <li>El límite de pérdida te ayuda a no gastar más de tu presupuesto.</li>
                        <li>Límite máximo de apuesta para protegerte contra una tirada o apuesta&nbsp; mayor a la prevista realizada en forma accidental.</li>
                        <li>Límite de tiempo de espera para evitar que permanezcas más tiempo del previsto en una sesión de juego.</li>
                        <li>REvaluación realista para recibir información sobre tus ganancias netas en tu sesión actual.</li>
                        <li>Bloqueo de cuenta es una herramienta que puedes usar si necesitas un breve descanso en tu juego.</li>
                        </ul>
                        <p style="text-align: justify;">Antes de comenzar a jugar, te recomendamos que eches un vistazo y hagas uso de nuestros límites. Para obtener más información sobre estas herramientas y cómo configurarlas, inicia sesión en tu cuenta y ve a "Mi perfil" -&gt; "Juego responsable".</p>
                        <p style="text-align: justify;">Si necesitas un descanso del juego, puedes configurar la autoexclusión desde la sección "Mi cuenta" mientras estás conectado. También puedes contactar con nuestro departamento de Atención al Cliente por <a href="/es/customer-service/contact-us" rel="noopener noreferrer" target="_blank">correo electrónico o vía chat</a>.&nbsp; &nbsp;</p>
                        <p style="text-align: justify;">Si necesitas hablar con alguien sobre cualquier inquietud que puedas tener con tus juegos de azar comunícate con una de las organizaciones, que puedes encontrar en <a href="#grupos-de-soporte">Grupos de Soporte</a>.</p>
                        <p>&nbsp;</p>
                        <hr>
                        <h2 id="bloquear-acceso" style="text-align: justify;">Bloquea el acceso a los sitios de juegos en línea con GamBlock</h2>
                        <p style="text-align: justify;">Si quieres bloquear el acceso a los sitios de juegos en línea desde tu ordenador, te recomendamos que uses un software como GamBlock. Por favor, lee más en&nbsp;<a href="http://www.gamblock.com/index.html" rel="noopener noreferrer" target="_blank">www.gamblock.com</a>.</p>'
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand === 'dbet') {
            foreach ($this->data as $row) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', $row['language'])
                    ->update(['value' => $row['value']]);
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'dbet') {
            foreach ($this->data as $row) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', $row['language'])
                    ->update(['value' => '']);
            }
        }
    }
}
