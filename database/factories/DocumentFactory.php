<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['tutorial', 'research', 'reference', 'documentation', 'article'];

        $titles = [
            'Pengenalan Machine Learning untuk Pemula',
            'Arsitektur Transformer dan Attention Mechanism',
            'Deep Learning dengan TensorFlow dan Keras',
            'Natural Language Processing: Konsep Dasar',
            'Computer Vision menggunakan CNN',
            'Reinforcement Learning: Teori dan Praktik',
            'Generative Adversarial Networks (GANs)',
            'Transfer Learning untuk Klasifikasi Gambar',
            'Optimasi Hyperparameter pada Neural Network',
            'Analisis Sentimen dengan BERT',
            'Implementasi Chatbot berbasis GPT',
            'Pengolahan Data Besar dengan Apache Spark',
            'Sistem Rekomendasi berbasis Collaborative Filtering',
            'Deteksi Objek dengan YOLO v8',
            'Pemrosesan Bahasa Alami Bahasa Indonesia',
            'Time Series Forecasting dengan LSTM',
            'Etika dan Bias dalam Artificial Intelligence',
            'Federated Learning: Privasi dalam AI',
            'Model Bahasa Besar (LLM): Arsitektur dan Aplikasi',
            'Retrieval-Augmented Generation (RAG) untuk Q&A',
            'Pengembangan API RESTful dengan Laravel',
            'Integrasi Database MySQL dengan Aplikasi Web',
            'Keamanan Aplikasi Web: Best Practices',
            'Microservices Architecture Pattern',
            'DevOps dan CI/CD Pipeline',
        ];

        $authors = [
            'Dr. Budi Santoso',
            'Prof. Siti Rahayu',
            'Andi Wijaya, M.Kom.',
            'Dr. Putri Lestari',
            'Rizky Pratama, S.T.',
            'Dr. Ahmad Fauzi',
            'Maya Indah, M.Sc.',
            'Dimas Nugroho, Ph.D.',
            'Rina Sari, M.T.',
            'Hendra Gunawan, S.Kom.',
        ];

        $title = $this->faker->randomElement($titles) . ' - ' . $this->faker->word();

        return [
            'title' => $title,
            'content' => $this->generateRealisticContent(),
            'category' => $this->faker->randomElement($categories),
            'author' => $this->faker->randomElement($authors),
            'source_url' => $this->faker->optional(0.7)->url(),
            'metadata' => [
                'tags' => $this->faker->randomElements(
                    ['AI', 'ML', 'DL', 'NLP', 'CV', 'Data Science', 'Python', 'PHP', 'Laravel', 'Web Dev', 'Database', 'Cloud'],
                    $this->faker->numberBetween(2, 5)
                ),
                'difficulty' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
                'language' => $this->faker->randomElement(['id', 'en']),
                'pages' => $this->faker->numberBetween(5, 120),
                'version' => $this->faker->randomFloat(1, 1.0, 3.0),
            ],
            'published_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /**
     * Generate realistic Indonesian academic content.
     */
    private function generateRealisticContent(): string
    {
        $paragraphs = [
            'Dalam era digital saat ini, kecerdasan buatan (AI) telah menjadi salah satu bidang teknologi yang paling berkembang pesat. Implementasi AI mencakup berbagai domain, mulai dari pemrosesan bahasa alami hingga visi komputer.',
            'Machine learning merupakan subset dari AI yang memungkinkan sistem untuk belajar dan meningkatkan performanya berdasarkan pengalaman tanpa perlu diprogram secara eksplisit. Metode ini menggunakan algoritma untuk menganalisis data, belajar dari data tersebut, dan membuat keputusan atau prediksi.',
            'Deep learning, yang merupakan bagian dari machine learning, menggunakan jaringan saraf tiruan dengan banyak lapisan (deep neural networks) untuk memodelkan abstraksi tingkat tinggi dalam data. Arsitektur seperti CNN, RNN, dan Transformer telah merevolusi berbagai bidang.',
            'Pemrosesan Bahasa Alami (NLP) adalah cabang AI yang berfokus pada interaksi antara komputer dan bahasa manusia. Teknologi NLP modern seperti BERT, GPT, dan T5 telah menunjukkan kemampuan yang luar biasa dalam memahami dan menghasilkan teks.',
            'Pengembangan aplikasi web modern memerlukan pemahaman mendalam tentang arsitektur client-server, RESTful API, database management, dan keamanan. Framework seperti Laravel menyediakan tools yang kuat untuk membangun aplikasi yang scalable dan maintainable.',
            'Integrasi database dengan aplikasi memerlukan perencanaan skema yang baik, normalisasi data, indexing yang tepat, dan query optimization. MySQL sebagai salah satu RDBMS paling populer menawarkan fitur-fitur enterprise-grade dengan komunitas yang besar.',
            'Model Context Protocol (MCP) memungkinkan integrasi yang seamless antara model bahasa besar dengan sumber data eksternal. Dengan MCP, LLM dapat mengakses database, API, dan resource lainnya secara terstruktur.',
            'Keamanan dalam pengembangan aplikasi AI mencakup aspek privasi data, fairness, transparency, dan accountability. Implementasi yang bertanggung jawab memerlukan audit berkala dan monitoring terhadap bias dalam model.',
        ];

        $count = $this->faker->numberBetween(3, 6);
        $selected = $this->faker->randomElements($paragraphs, $count);

        return implode("\n\n", $selected);
    }
}
