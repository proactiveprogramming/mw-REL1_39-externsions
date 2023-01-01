-- Copyright (c) 2010 University of Macau
--
-- Licensed under the Educational Community License, Version 2.0 (the "License");
-- you may not use this file except in compliance with the License. You may
-- obtain a copy of the License at
--
-- http://www.osedu.org/licenses/ECL-2.0
--
-- Unless required by applicable law or agreed to in writing,
-- software distributed under the License is distributed on an "AS IS"
-- BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
-- or implied. See the License for the specific language governing
-- permissions and limitations under the License.


CREATE OR REPLACE FUNCTION calculate_degree(
  p_user1 INTEGER,
  p_user2 NUMERIC,
  p_page NUMERIC,
  p_max_time_a DATE,
  p_min_time_a DATE,
  p_max_time_b DATE,
  p_min_time_b DATE
)
RETURNS DECIMAL(16, 10) AS $$
DECLARE
  wv_time_a NUMERIC(18, 10);
  l_an_a INTEGER;
  l_an_b INTEGER;
  l_tn INTEGER DEFAULT 0;
  l_am_a INTEGER;
  l_am_b INTEGER;
  l_tm INTEGER DEFAULT 0;
  l_minor INTEGER DEFAULT 0;
  CM DECIMAL(3, 2) DEFAULT 0.05;

  l_intersect DECIMAL(18, 10);
  l_la INTEGER;
  l_la_start DATE default p_min_time_a;
  l_la_end DATE default p_max_time_a;
  l_lb INTEGER;
  l_lb_start DATE default p_min_time_b;
  l_lb_end DATE default p_max_time_b;
  l_distance DECIMAL(18, 10);

BEGIN
  -- Calculate the time intersection
  l_la := ABS(l_la_start - l_la_end) + 1;

  -- Case 5, No intersection
  IF (l_la_end < l_lb_start OR l_la_start > l_lb_end) THEN
    -- If l_intersect = 0, Count the distance
    IF (l_la_start < l_lb_start) THEN
      l_distance := ABS(l_lb_start - l_la_end) + 1;
    ELSE
      l_distance := ABS(l_la_start - l_lb_end) + 1;
    END IF;
    -- +1 on distance to avoid l_intersect = 1 if two DATEs are continued
    l_intersect := ROUND(1 / SQRT(l_distance), 10);
  -- Case 1
  ELSEIF (l_la_start <= l_lb_start AND l_la_end <= l_lb_end) THEN
    l_intersect := ABS(l_lb_start - l_la_end) + 1;
  -- Case 2
  ELSEIF (l_lb_start <= l_la_start AND l_lb_end <= l_la_end) THEN
    l_intersect := ABS(l_la_start - l_lb_end) + 1;
  -- Case 3
  ELSEIF (l_la_start <= l_lb_start AND l_lb_end <= l_la_end) THEN
    l_intersect := ABS(l_lb_start - l_lb_end) + 1; -- l_lb
  -- Case 4
  ELSEIF (l_lb_start <= l_la_start AND l_la_end <= l_lb_end) THEN
    l_intersect := l_la;
  ELSE
    l_intersect := -1; -- Error
  END IF;

  wv_time_a := COALESCE(ROUND(l_intersect / l_la, 10), 0);

  SELECT COALESCE(SUM(CASE WHEN rev_minor_edit = 1 THEN 1 ELSE 0 END), 0),
         COALESCE(SUM(CASE WHEN rev_minor_edit = 0 THEN 1 ELSE 0 END), 0),
         COALESCE(SUM(CASE WHEN rev_user = p_user1 AND rev_minor_edit = 1 THEN 1 ELSE 0 END), 0),
         COALESCE(SUM(CASE WHEN rev_user = p_user2 AND rev_minor_edit = 1 THEN 1 ELSE 0 END), 0),
         COALESCE(SUM(CASE WHEN rev_user = p_user1 AND rev_minor_edit = 0 THEN 1 ELSE 0 END), 0),
         COALESCE(SUM(CASE WHEN rev_user = p_user2 AND rev_minor_edit = 0 THEN 1 ELSE 0 END), 0)
  INTO l_tm, l_tn, l_am_a, l_am_b, l_an_a, l_an_b
  FROM revision
  WHERE rev_page = p_page;

  IF (l_tm <> 0) THEN
    l_minor := ROUND((CM * LEAST(l_am_a, l_am_b)) / l_tm);
  END IF;

  RAISE LOG 'wv_time_a %, l_minor %', wv_time_a, l_minor;
  RAISE LOG 'l_an_a %, l_an_b %, l_tn %, op %', l_an_a, l_an_b, l_tn, (ROUND(CAST(LEAST(l_an_a, l_an_b) AS DECIMAL) / CAST(l_tn AS DECIMAL), 10) + l_minor) * wv_time_a;

  RETURN COALESCE((ROUND(CAST(LEAST(l_an_a, l_an_b) AS DECIMAL) / CAST(l_tn AS DECIMAL), 10) + l_minor) * wv_time_a, 0);
END ;
$$ LANGUAGE plpgsql;
