-- Añadir más juegos a la base de datos
USE game_platform;

INSERT INTO games (name, slug, description, icon, is_active) VALUES
('Tetris', 'tetris', 'Clásico juego de bloques: encaja las piezas antes de que lleguen arriba.', 'icons/tetris.png', 1),
('Pac-Man', 'pacman', 'Come todos los puntos mientras evitas a los fantasmas.', 'icons/pacman.png', 1),
('Asteroides', 'asteroides', 'Destruye asteroides y evita colisionar con ellos.', 'icons/asteroides.png', 1),
('Pong', 'pong', 'El clásico juego de ping pong: rebota la pelota con tu paleta.', 'icons/pong.png', 1),
('Space Invaders', 'space-invaders', 'Defiende la Tierra de los invasores espaciales.', 'icons/space-invaders.png', 1),
('Breakout', 'breakout', 'Rompe todos los bloques con la pelota.', 'icons/breakout.png', 1),
('Frogger', 'frogger', 'Ayuda a la rana a cruzar la carretera y el río.', 'icons/frogger.png', 1),
('Galaga', 'galaga', 'Dispara a las naves enemigas en este clásico arcade.', 'icons/galaga.png', 1),
('Donkey Kong', 'donkey-kong', 'Escala las plataformas para rescatar a la princesa.', 'icons/donkey-kong.png', 1),
('Centipede', 'centipede', 'Destruye el ciempiés antes de que llegue al suelo.', 'icons/centipede.png', 1),
('Q*bert', 'qbert', 'Salta en los cubos para cambiar su color.', 'icons/qbert.png', 1),
('Dig Dug', 'dig-dug', 'Cava túneles y elimina enemigos con tu bomba.', 'icons/dig-dug.png', 1),
('Joust', 'joust', 'Vuela sobre tu avestruz y derrota a los enemigos.', 'icons/joust.png', 1),
('Robotron', 'robotron', 'Sobrevive a las oleadas de robots enemigos.', 'icons/robotron.png', 1),
('Defender', 'defender', 'Protege a los humanos de los alienígenas.', 'icons/defender.png', 1),
('Missile Command', 'missile-command', 'Destruye los misiles antes de que destruyan las ciudades.', 'icons/missile-command.png', 1),
('Tempest', 'tempest', 'Dispara a los enemigos mientras giras por el túnel.', 'icons/tempest.png', 1),
('Zaxxon', 'zaxxon', 'Vuela en 3D y destruye las bases enemigas.', 'icons/zaxxon.png', 1),
('Pole Position', 'pole-position', 'Conduce el coche de carreras más rápido.', 'icons/pole-position.png', 1),
('Tron', 'tron', 'Entra al mundo digital y lucha contra el MCP.', 'icons/tron.png', 1);

