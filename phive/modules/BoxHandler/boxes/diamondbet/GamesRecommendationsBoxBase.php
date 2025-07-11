<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
class GamesRecommendationsBoxBase extends DiamondBox
{
    public function printHTML()
    {
        if(phive('GamesRecommendations')->isEnabled() && phive('GamesRecommendations')->testPageAllowed()){
        ?>
        <!-- This page is a temporary solution which will be removed. We don't need to fluid our regular css with this styles -->
        <style>
            .input-container, .ab-testing-info {
                margin: 0 auto;
                margin-top: 20px;
                margin-bottom: 20px;
                text-align: left;
            }

            .input-container {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-bottom: 20px;
            }

            .input-container input[type="text"] {
                width: 200px;
                padding: 10px 15px;
                border: 1px solid #ddd;
                border-radius: 25px 0 0 25px; /* Rounded corners for the left side */
                outline: none;
                transition: 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .input-container input[type="text"]:focus {
                border-color: #007bff;
                box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
            }

            .input-container button {
                padding: 10px 20px;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 0 25px 25px 0; /* Rounded corners for the right side */
                cursor: pointer;
                font-size: 1rem;
                transition: background-color 0.3s, box-shadow 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .input-container button:hover {
                background-color: #0056b3;
                box-shadow: 0 4px 8px rgba(0, 86, 179, 0.3);
            }

            .input-container .clear-button {
                margin-left: -1px; /* Remove spacing between input and button */
                border-radius: 0 25px 25px 0;
            }

            .input-container .submit-button {
                margin-left: 10px;
                border-radius: 25px;
            }

            .gallery {
                width: 100%;
                margin: 0 auto;
                margin-top: 10px;
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                justify-content: flex-start;
            }

            .category {
                margin-bottom: 30px;
                padding-bottom: 10px;
                width: 100%;
            }

            .category h2 {
                font-size: 1.5rem;
                margin-bottom: 10px;
                color: white;
            }

            .category p {
                font-size: 0.9rem;
                color: white;
                margin-bottom: 10px;
            }

            .model {
                font-size: 0.9rem;
                color: #888;
                margin-bottom: 10px;
                font-style: italic;
            }

            .image-row {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                justify-content: flex-start;
                width: 100%;
            }

            .image-item-container {
                width: 100px;
                text-align: center;
                color: white;
            }

            .image-item {
                width: 100px;
                height: 100px;
                border-radius: 8px;
                transition: transform 0.2s;
                object-fit: cover;
            }

            .image-item:hover {
                transform: scale(1.05);
            }

            .game-name {
                font-size: 0.85rem;
                color: white;
                margin-top: 5px;
                word-break: break-word;
            }

            /* Lightbox styling */
            .lightbox {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: none;
                justify-content: center;
                align-items: center;
            }

            .lightbox img {
                max-width: 90%;
                max-height: 90%;
            }

            .lightbox:target {
                display: flex;
            }
        </style>

        <script>
            function clearPlayerId() {
                const playerIdField = document.getElementById('playerId');
                playerIdField.value = ''; // Clear the playerId field
                playerIdField.form.submit(); // Submit the form
            }
        </script>

            <div class="input-container">
                <form method="GET" action="">
                    <input type="text" id="playerId" name="playerId" value="<?php echo htmlspecialchars($_GET['playerId']) ?>" required placeholder="Player's ID">
                    <button type="button" class="clear-button" onclick="clearPlayerId()">Clear</button>
                    <button type="submit" class="submit-button">Get Recommendations</button>
                </form>
            </div>

        <?php

        $playerId = intval($_GET['playerId']);

        if ($playerId) {
            $data = phive('GamesRecommendations')->getSectionRecommendations($playerId);
        } else {
            $unloggedRecommendations = phive('GamesRecommendations')->getUnloggedRecommendations();
        }

// Display AB testing information if available
        if (!empty($data['abTesting']) && $playerId) {
            echo '<div class="ab-testing-info">';
            echo '<p><strong>AB Testing Enabled:</strong> ' . ($data['abTesting']['enabled'] ? 'Yes' : 'No') . '</p>';
            echo '<p><strong>Group:</strong> ' . htmlspecialchars($data['abTesting']['abGroup']) . '</p>';
            echo '<p><strong>Description:</strong> ' . htmlspecialchars($data['abTesting']['abGroupDescription']) . '</p>';
            echo '</div>';
        } elseif (!empty($unloggedRecommendations)){
            echo '<div class="ab-testing-info">';
            echo '<p><h2>Unlogged recommendations</h2>';
            echo '</div>';
        }

// Function to extract model parameter from URL
        function getModelFromUrl($url) {
            $parsedUrl = parse_url($url);
            parse_str($parsedUrl['query'], $queryParams);
            return $queryParams['model'] ?? null;
        }
        ?>

        <div class="gallery">
            <?php
            if (!empty($data['values'])) {
                foreach ($data['values'] as $section) {
                    $model = getModelFromUrl($section['url']);

                    if ($model) {
                    $recommendations = phive('GamesRecommendations')->getPlayerRecommendations($playerId, ['model' => $model]);
                    $gamesData = phive('MicroGames')->getFromExtGameNameArr($recommendations['gamesId'], !isMobileSite() ? 0 : 1);
                    echo '<div class="category">';
                    echo '<h2>' . htmlspecialchars(str_replace('{game_name}', $recommendations['gameName'] ?? '', $section['sectionName'])) . '</h2>';
                    echo '<p>' . htmlspecialchars($section['sectionDescription']) . '</p>';

                    echo '<p class="model">Model: ' . htmlspecialchars($model) . '</p>';



                        echo '<div class="image-row">';
                        if (!empty($recommendations)) {
                            foreach ($gamesData as $game) {
                                $gameName = $game['game_name'];
                                $gameId = $game['game_id'];
                                $image = fupUri('thumbs/' . $gameId . '_c.jpg', true, 'thumbs/nopic_c.png');

                            echo '<div class="image-item-container">';
                            echo '<a onclick="playGameDepositCheckBonus(\'' . $gameId . '\')" style="cursor: pointer;">';
                            echo '<img src="' . $image . '" alt="" class="image-item">';
                            echo '</a>';
                            echo '<p class="game-name">' . htmlspecialchars($gameName) . '</p>';
                            echo '</div>';

                            // Lightbox for image preview
                            echo '<div id="lightbox-' . $gameId . '" class="lightbox">';
                            echo '<img src="' . $image . '" alt="">';
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p class="model">No model found for this section.</p>';
                    }

                    echo '</div>';
                }
                }
            } elseif(!empty($unloggedRecommendations)) {
                $gamesData = phive('MicroGames')->getFromExtGameNameArr($unloggedRecommendations, !isMobileSite() ? 0 : 1);

                echo '<div class="gallery">';
                foreach ($gamesData as $game) {
                    $gameName = $game['game_name'];
                    $gameId = $game['game_id'];
                    $image = fupUri('thumbs/' . $gameId . '_c.jpg', true, 'thumbs/nopic_c.png');

                    echo '<div class="image-item-container">';
                    echo '<a onclick="playGameDepositCheckBonus(\'' . $gameId . '\')" style="cursor: pointer;">';
                    echo '<img src="' . $image . '" alt="" class="image-item">';
                    echo '</a>';
                    echo '<p class="game-name">' . htmlspecialchars($gameName) . '</p>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>No categories found.</p>';
            }
            ?>
        </div>

        <?php
        } else {
            echo "Recommendations are not available";
        }
    }
}
