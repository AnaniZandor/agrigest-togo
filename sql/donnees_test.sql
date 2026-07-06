-- =====================================================
-- DONNEES TEST - AgriGest Togo
-- =====================================================

-- Mot de passe pour tous les comptes : password123
-- Hash: $2y$10$N9qo8ucoathWuc8c7XoW3OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW

-- ---------------------------------------------------------
-- UTILISATEURS
-- ---------------------------------------------------------

INSERT INTO UTILISATEUR VALUES 
('ADM-001', 'Dupont', 'Jean', 'admin@agrigest.togo', '$2y$10$N9qo8ucoathWuc8c7XoW3OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'admin'),
('RSP-COP001-001', 'Martin', 'Marie', 'marie.martin@agrigest.togo', '$2y$10$N9qo8ucoathWuc8c7XoW3OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'responsable'),
('RSP-COP002-001', 'Koffi', 'Ama', 'ama.koffi@agrigest.togo', '$2y$10$N9qo8ucoathWuc8c7XoW3OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'responsable'),
('AGR-COP001-001', 'Sow', 'Ibrahim', 'ibrahim.sow@agrigest.togo', '$2y$10$N9qo8ucoathWuc8c7XoW3OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'agriculteur'),
('AGR-COP001-002', 'Diallo', 'Fatoumata', 'fatoumata.diallo@agrigest.togo', '$2y$10$N9qo8ucoathWuc8c7XoW3OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'agriculteur'),
('AGR-COP002-001', 'Akakpo', 'Komivi', 'komivi.akakpo@agrigest.togo', '$2y$10$N9qo8ucoathWuc8c7XoW3OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'agriculteur'),
('AGR-COP002-002', 'Mensah', 'Gifty', 'gifty.mensah@agrigest.togo', '$2y$10$N9qo8ucoathWuc8c7XoW3OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW', 'agriculteur');

-- ---------------------------------------------------------
-- COOPERATIVES
-- ---------------------------------------------------------

INSERT INTO COOPERATIVE VALUES 
('COP-001', 'Coopérative Agricole du Togo', 'Lomé, Région Maritime'),
('COP-002', 'Coopérative des Producteurs du Centre', 'Atakpamé, Région Centrale');

-- ---------------------------------------------------------
-- RESPONSABLES
-- ---------------------------------------------------------

INSERT INTO RESPONSABLE VALUES 
('RSP-COP001-001', 'COP-001'),
('RSP-COP002-001', 'COP-002');

-- ---------------------------------------------------------
-- AGRICULTEURS
-- ---------------------------------------------------------

INSERT INTO AGRICULTEUR VALUES 
('AGR-COP001-001', '+228 71 11 22 33', 'COP-001'),
('AGR-COP001-002', '+228 71 44 55 66', 'COP-001'),
('AGR-COP002-001', '+228 71 77 88 99', 'COP-002'),
('AGR-COP002-002', '+228 71 10 20 30', 'COP-002');

-- ---------------------------------------------------------
-- ZONES AGROECOLOGIQUES
-- ---------------------------------------------------------

INSERT INTO ZONE_AGROECOLOGIQUE VALUES 
('ZON-001', 'Région Maritime'),
('ZON-002', 'Région Centrale');

-- ---------------------------------------------------------
-- CULTURES
-- ---------------------------------------------------------

INSERT INTO CULTURE VALUES 
('CUL-001', 'Maïs', 120),
('CUL-002', 'Riz', 150),
('CUL-003', 'Haricot', 90),
('CUL-004', 'Manioc', 365);

-- ---------------------------------------------------------
-- INTRANTS
-- ---------------------------------------------------------

INSERT INTO INTRANT VALUES 
('INT-001', 'Urée', 'engrais', 'kg'),
('INT-002', 'Phosphate diammonique (DAP)', 'engrais', 'kg'),
('INT-003', 'Maïs Hybrid Premium', 'semence', 'kg'),
('INT-004', 'Riz Hybrid Elite', 'semence', 'kg'),
('INT-005', 'Irrigation - Eau de puits', 'eau', 'litre');

-- ---------------------------------------------------------
-- SAISONS
-- ---------------------------------------------------------

INSERT INTO SAISON VALUES 
('SAI-2025P', 'Saison Pluvieuse 2025', '2025-04-01', '2025-08-31'),
('SAI-2025S', 'Saison Sèche 2025', '2025-11-01', '2026-03-31');

-- ---------------------------------------------------------
-- PARCELLES
-- ---------------------------------------------------------

INSERT INTO PARCELLE VALUES 
('PAR-ZON001-001', 'Parcelle Nord', 'Km 8, Route de Kévé', 2.5, 'ZON-001', 'AGR-COP001-001'),
('PAR-ZON001-002', 'Parcelle Sud', 'Km 12, Route de Kévé', 1.8, 'ZON-001', 'AGR-COP001-001'),
('PAR-ZON001-003', 'Parcelle Est', 'Km 5, Route du Port', 3.2, 'ZON-001', 'AGR-COP001-002'),
('PAR-ZON002-001', 'Parcelle Centrale', 'Atakpamé, Route de Kpalimé', 2.0, 'ZON-002', 'AGR-COP002-001'),
('PAR-ZON002-002', 'Parcelle Ouest', 'Atakpamé, Zone Agro-Pastorale', 1.5, 'ZON-002', 'AGR-COP002-002');

-- ---------------------------------------------------------
-- PLANTATIONS
-- ---------------------------------------------------------

INSERT INTO PLANTATION VALUES 
('PLT-001', '2025-04-15', 'PAR-ZON001-001', 'CUL-001', 'SAI-2025P'),
('PLT-002', '2025-04-20', 'PAR-ZON001-002', 'CUL-002', 'SAI-2025P'),
('PLT-003', '2025-05-01', 'PAR-ZON001-003', 'CUL-003', 'SAI-2025P'),
('PLT-004', '2025-04-10', 'PAR-ZON002-001', 'CUL-001', 'SAI-2025P'),
('PLT-005', '2025-05-05', 'PAR-ZON002-002', 'CUL-004', 'SAI-2025P');

-- ---------------------------------------------------------
-- UTILISER (Intrants utilisés pour les plantations)
-- ---------------------------------------------------------

INSERT INTO UTILISER VALUES 
('PLT-001', 'INT-001', 50, '2025-04-20'),
('PLT-001', 'INT-003', 25, '2025-04-15'),
('PLT-002', 'INT-002', 40, '2025-04-25'),
('PLT-002', 'INT-004', 30, '2025-04-20'),
('PLT-003', 'INT-001', 20, '2025-05-05'),
('PLT-004', 'INT-001', 55, '2025-04-15'),
('PLT-004', 'INT-003', 28, '2025-04-10'),
('PLT-005', 'INT-001', 35, '2025-05-10');

-- ---------------------------------------------------------
-- RECOLTES
-- ---------------------------------------------------------

INSERT INTO RECOLTE VALUES 
('REC-001', '2025-08-15', 750.50, 'PLT-001'),
('REC-002', '2025-09-10', 1200.75, 'PLT-002'),
('REC-003', '2025-07-25', 450.25, 'PLT-003'),
('REC-004', '2025-08-20', 850.00, 'PLT-004');