UPDATE Site
  LEFT JOIN (select distinct SiteFK from Survey JOIN Plant ON Plant.ID = Survey.PlantFK WHERE Survey.LocalDate > '2023-01-01') AS Survey
  ON Site.ID = Survey.SiteFK
  SET Site.Active = 0
  WHERE Survey.SiteFK IS NULL AND Site.DateEstablished < '2024-01-01';
