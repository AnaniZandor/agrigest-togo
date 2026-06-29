USE agrigest_togo;

INSERT INTO COOPERATIVE (id_coop, nom_coop, localisation_coop) 
VALUES ('COP-001', 'Cooperative Test Lome', 'Lome');

INSERT INTO UTILISATEUR (id_utilisateur, nom, prenom, email, mot_de_passe, role, id_coop) 
VALUES ('UTL-001', 'Admin', 'Test', 'admin@test.tg', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYUkpsX3SfDqFkVTW0aDQNUEf3xVHVlS', 'admin', NULL);