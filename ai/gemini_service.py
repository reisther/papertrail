import os
import google.generativeai as genai

api_key = os.getenv("GEMINI_API_KEY")

if not api_key:
    raise RuntimeError("GEMINI_API_KEY is not set. Add it to your local .env or environment variables.")

genai.configure(api_key=api_key)

# Use a supported model
model = genai.GenerativeModel("gemini-2.5-flash")


def analyze_titles(title1, title2, title3, title4, title5):

    prompt = f"""
Analyze these thesis titles:

1. {title1}
2. {title2}
3. {title3}
4. {title4}
5. {title5}

Determine:

- Main research field
- Keywords
- Technologies involved
- Suggested adviser expertise

Available adviser expertise:

- Machine Learning
- AI Integration
- Cybersecurity
- IoT
- Cloud Computing

Return the answer in bullet form.
"""

    try:
        print("Sending request to Gemini...")

        response = model.generate_content(prompt)

        print("Gemini replied!")

        return response.text

    except Exception as e:
        print("Gemini error:", e)

        return str(e)
