from fastapi import FastAPI
from pydantic import BaseModel
from gemini_service import analyze_titles

app = FastAPI()

class TitleRequest(BaseModel):
    title1: str
    title2: str
    title3: str
    title4: str
    title5: str


@app.post("/analyze")
def analyze(data: TitleRequest):

    print("Request received")

    result = analyze_titles(
        data.title1,
        data.title2,
        data.title3,
        data.title4,
        data.title5
    )

    print("Finished analyzing")

    return {
        "analysis": result
    }