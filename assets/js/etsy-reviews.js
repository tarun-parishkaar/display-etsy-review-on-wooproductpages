document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.etsy-reviews');
    if (!container || !EtsyReviewsData.etsyProductId) return;

    const reviewList = container.querySelector('.etsy-review-list');

    fetch(`${EtsyReviewsData.ajaxUrl}?action=get_etsy_reviews&listing_id=${EtsyReviewsData.etsyProductId}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            return res.json();
        })
        .then(response => {
            if (!response.success || !response.data || !response.data.results) {
                reviewList.innerHTML = '<p>No reviews found.</p>';
                return;
            }

            reviewList.innerHTML = '';
            response.data.results.forEach(review => {
                const div = document.createElement('div');
                div.classList.add('etsy-review');
                div.innerHTML = `
                    <p><strong>${review.author.display_name}</strong> (${review.rating}/5):</p>
                    <p>${review.review}</p>
                    <hr />
                `;
                reviewList.appendChild(div);
            });
        })
        .catch(error => {
            console.error('Error fetching reviews:', error);
            reviewList.innerHTML = '<p>Unable to load reviews.</p>';
        });
});
