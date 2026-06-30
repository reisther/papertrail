from sqlalchemy import create_engine, text


DB_HOST = "localhost"
DB_USER = "root"
DB_PASSWORD = ""
DB_NAME = "test"

DATABASE_URL = (
    f"mysql+pymysql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}/{DB_NAME}"
)

engine = create_engine(DATABASE_URL)


def get_all_advisers():

    query = text("""
        SELECT
            users.id,
            users.firstname,
            users.lastname,
            adviser_expertise.machine_learning,
            adviser_expertise.ai_integration,
            adviser_expertise.cybersecurity,
            adviser_expertise.iot,
            adviser_expertise.cloud_computing
        FROM users
        JOIN adviser_expertise
            ON users.id = adviser_expertise.adviser_id
        WHERE users.role = 'Teacher'
    """)

    with engine.connect() as conn:
        result = conn.execute(query)

        advisers = []

        for row in result:
            advisers.append({
                "id": row.id,
                "name": f"{row.firstname} {row.lastname}",
                "machine_learning": row.machine_learning,
                "ai_integration": row.ai_integration,
                "cybersecurity": row.cybersecurity,
                "iot": row.iot,
                "cloud_computing": row.cloud_computing
            })

        return advisers