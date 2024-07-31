ALTER TABLE Plant ADD COLUMN Moved BOOLEAN;

UPDATE Plant SET Moved = TRUE, Circle = Circle * -1  WHERE Circle < 0;
