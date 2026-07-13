-- =====================================================
-- AGRIGEST TOGO - Requetes metier
-- 5 requetes SELECT repondant a des besoins identifies
-- dans l'analyse des besoins (chapitre 2 du rapport)
-- =====================================================

USE agrigest_togo;

-- ---------------------------------------------------------
-- Requete 1 : Rendement moyen par culture
-- Besoin : F6 - permettre au responsable de comparer
-- la performance des differentes cultures
-- ---------------------------------------------------------
SELECT
    c.nom_culture,
    ROUND(AVG(r.rendement), 2) AS rendement_moyen,
    COUNT(r.id_recolte) AS nombre_recoltes
FROM RECOLTE r
JOIN PLANTATION p ON r.id_plantation = p.id_plantation
JOIN CULTURE c ON p.id_culture = c.id_culture
GROUP BY c.nom_culture
ORDER BY rendement_moyen DESC;

-- ---------------------------------------------------------
-- Requete 2 : Quantite d'intrants utilises par saison
-- Besoin : F4 - suivre la consommation d'intrants
-- (engrais, semences, eau) periode par periode
-- ---------------------------------------------------------
SELECT
    s.libelle_saison,
    i.nom_intrant,
    i.type_intrant,
    SUM(u.quantite_utilisee) AS quantite_totale,
    i.unite_mesure
FROM UTILISER u
JOIN PLANTATION p ON u.id_plantation = p.id_plantation
JOIN SAISON s ON p.id_saison = s.id_saison
JOIN INTRANT i ON u.id_intrant = i.id_intrant
GROUP BY s.libelle_saison, i.nom_intrant, i.type_intrant, i.unite_mesure
ORDER BY s.libelle_saison, quantite_totale DESC;

-- ---------------------------------------------------------
-- Requete 3 : Liste des recoltes par agriculteur
-- Besoin : F5 - tracabilite des recoltes par exploitant
-- ---------------------------------------------------------
SELECT
    u.nom,
    u.prenom,
    pa.nom_parcelle,
    c.nom_culture,
    r.date_recolte,
    r.rendement
FROM RECOLTE r
JOIN PLANTATION p ON r.id_plantation = p.id_plantation
JOIN PARCELLE pa ON p.id_parcelle = pa.id_parcelle
JOIN AGRICULTEUR a ON pa.id_agri = a.id_agri
JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
JOIN CULTURE c ON p.id_culture = c.id_culture
ORDER BY u.nom, u.prenom, r.date_recolte;

-- ---------------------------------------------------------
-- Requete 4 : Superficie totale geree par agriculteur
-- Besoin : F2 - vue consolidee du foncier par exploitant
-- ---------------------------------------------------------
SELECT
    u.nom,
    u.prenom,
    COUNT(pa.id_parcelle) AS nombre_parcelles,
    COALESCE(SUM(pa.superficie), 0) AS superficie_totale_ha
FROM AGRICULTEUR a
JOIN UTILISATEUR u ON a.id_agri = u.id_utilisateur
LEFT JOIN PARCELLE pa ON pa.id_agri = a.id_agri
GROUP BY u.nom, u.prenom
ORDER BY superficie_totale_ha DESC;

-- ---------------------------------------------------------
-- Requete 5 : Bilan de production par cooperative
-- Besoin : F6 - rapport de production consolide, destine
-- au responsable de cooperative
-- ---------------------------------------------------------
SELECT
    co.nom_coop,
    COUNT(DISTINCT a.id_agri) AS nombre_agriculteurs,
    COUNT(r.id_recolte) AS nombre_recoltes,
    COALESCE(SUM(r.rendement), 0) AS production_totale
FROM COOPERATIVE co
LEFT JOIN AGRICULTEUR a ON a.id_coop = co.id_coop
LEFT JOIN PARCELLE pa ON pa.id_agri = a.id_agri
LEFT JOIN PLANTATION p ON p.id_parcelle = pa.id_parcelle
LEFT JOIN RECOLTE r ON r.id_plantation = p.id_plantation
GROUP BY co.nom_coop
ORDER BY production_totale DESC;