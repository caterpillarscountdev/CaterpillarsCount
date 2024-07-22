ALTER TABLE Plant ADD COLUMN Moved BOOLEAN;

UPDATE Plant SET Moved = TRUE WHERE Circle < 0;

; Set Circle, Code, and Orientation to active for moved

// TODO this join is too greedy
SELECT P1.ID, P2.ID, P1.SiteFK, P2.SiteFK, P1.Circle, P2.Circle, P1.Orientation, P2.Orientation FROM Plant P1 Join Plant P2 ON P1.ID != P2.ID AND P1.SiteFK = P2.SiteFK AND P1.Circle = ABS(P2.Circle) AND P1.Orientation = P2.Orientation WHERE P2.Circle < 0;

UPDATE Plant SET Circle = Circle * -1 WHERE Circle < 0;
