-- Seed: Typy pokut
-- 4 typy (neomluvená absence = sjednocení "na daily" + "pozdní příchod")

USE punishment_app;

INSERT IGNORE INTO penalty_types (name, default_amount) VALUES
    ('chybějící výkazy vzhledem k docházce při kontrole', 20.00),
    ('nepřeplánovaný červený sloupec při kontrole', 20.00),
    ('neaktualizovaná planning tabulka při kontrole', 20.00),
    ('neomluvená absence', 20.00);
