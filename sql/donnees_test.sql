USE agrigest_togo;

INSERT INTO COOPERATIVE (id_coop, nom_coop, localisation_coop) 
VALUES ('COP-001', 'Cooperative Test Lome', 'Lome');

INSERT INTO UTILISATEUR (id_utilisateur, nom, prenom, email, mot_de_passe, role, id_coop) 
VALUES ('UTL-001', 'Admin', 'Test', 'admin@test.tg', '$2y$10$ceaICyrn7tEragyclj0HieqObQApwjrN1lVIxG68/nQYD27Nvt4mS', 'admin', NULL);
