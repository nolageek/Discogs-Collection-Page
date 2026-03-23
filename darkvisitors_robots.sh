curl -X POST "https://api.darkvisitors.com/robots-txts" ^
-H "Authorization: Bearer fef7ecb8-5548-4166-973b-ba6c48af6f17" ^
-H "Content-Type: application/json" ^
-d "{\"agent_types\": [\"AI Data Scraper\", \"Undocumented AI Agent\"], \"disallow\": \"/\"}" ^
-o robots.txt
